<?php

namespace Tests\Services\Storelocator;

use App\Constants\Common;
use App\Constants\MongoCollection;
use App\DocumentBuilder\Setting;
use App\Services\MongoManager;
use App\Services\Storelocator\SettingService;
use Mockery;
use Mockery\MockInterface;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class SettingServiceMockeryTest extends TestCase
{
    private SettingService $settingService;
    private MockInterface $mongoManagerMock;
    private string $mongoDbStorelocatorUrl;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mongoManagerMock = Mockery::mock(MongoManager::class);
        $this->mongoDbStorelocatorUrl = 'test_database_url';
        
        $this->mongoManagerMock->shouldReceive('setDatabase')
            ->with($this->mongoDbStorelocatorUrl)
            ->once();
            
        $this->mongoManagerMock->shouldReceive('selectCollection')
            ->with(MongoCollection::SETTING)
            ->once();
        
        $this->settingService = new SettingService(
            $this->mongoManagerMock,
            $this->mongoDbStorelocatorUrl
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testSaveNewSettingWithMockery(): void
    {
        $companyId = 'test_company_id';
        $newSetting = [
            'setting1' => 'value1',
            'setting2' => 'value2'
        ];
        
        $expectedSettingDocument = [
            Common::COMPANY_ID => $companyId,
            'setting1' => 'value1',
            'setting2' => 'value2',
            Common::IDENTIFIER => 'generated_identifier',
            Common::TOKEN => 'generated_token'
        ];

        // Mock: pas de setting existant
        $this->mongoManagerMock->shouldReceive('getOneBy')
            ->with([Common::COMPANY_ID => $companyId])
            ->andReturn(null)
            ->once();

        // Mock Setting::build avec Mockery
        $settingMock = Mockery::mock('alias:' . Setting::class);
        $settingMock->shouldReceive('build')
            ->with($companyId, $newSetting, null, null)
            ->andReturn($expectedSettingDocument)
            ->once();

        $this->mongoManagerMock->shouldReceive('updateWithoutChangeBy')
            ->with([Common::COMPANY_ID => $companyId], Mockery::type('array'))
            ->once();

        $result = $this->settingService->save($companyId, $newSetting);

        $this->assertArrayHasKey(Common::CREATED_AT, $result);
        $this->assertInstanceOf(UTCDateTime::class, $result[Common::CREATED_AT]);
    }

    public function testSaveExistingSettingWithUpdateUsingMockery(): void
    {
        $companyId = 'test_company_id';
        $newSetting = ['setting1' => 'updated_value'];
        $existingDocument = [
            Common::COMPANY_ID => $companyId,
            Common::IDENTIFIER => 'existing_identifier',
            Common::TOKEN => 'existing_token'
        ];

        $expectedSettingDocument = [
            Common::COMPANY_ID => $companyId,
            'setting1' => 'updated_value',
            Common::IDENTIFIER => 'existing_identifier',
            Common::TOKEN => 'existing_token'
        ];

        $this->mongoManagerMock->shouldReceive('getOneBy')
            ->with([Common::COMPANY_ID => $companyId])
            ->andReturn($existingDocument)
            ->once();

        // Mock Setting::build avec Mockery
        $settingMock = Mockery::mock('alias:' . Setting::class);
        $settingMock->shouldReceive('build')
            ->with($companyId, $newSetting, 'existing_token', 'existing_identifier')
            ->andReturn($expectedSettingDocument)
            ->once();

        $this->mongoManagerMock->shouldReceive('updateWithoutChangeBy')
            ->with([Common::COMPANY_ID => $companyId], $expectedSettingDocument)
            ->once();

        $result = $this->settingService->save($companyId, $newSetting, true);

        $this->assertArrayNotHasKey(Common::CREATED_AT, $result);
        $this->assertEquals($expectedSettingDocument, $result);
    }

    public function testGetSettingsWithComplexScenario(): void
    {
        $identifier = 'test_identifier';
        $token = 'test_token';
        $globalSettings = [
            'theme' => 'dark',
            'language' => 'fr',
            'notifications' => [
                'email' => true,
                'sms' => false
            ]
        ];
        $documentResult = [Common::GLOBAL_SETTINGS => $globalSettings];

        $this->mongoManagerMock->shouldReceive('getOneBy')
            ->with(
                [Common::IDENTIFIER => $identifier, Common::TOKEN => $token],
                [
                    'projection' => [
                        Common::GLOBAL_SETTINGS => 1,
                        '_id' => 0
                    ]
                ]
            )
            ->andReturn($documentResult)
            ->once();

        $result = $this->settingService->getSettings($identifier, $token);

        $this->assertEquals($globalSettings, $result);
        $this->assertArrayHasKey('theme', $result);
        $this->assertArrayHasKey('notifications', $result);
        $this->assertEquals('dark', $result['theme']);
        $this->assertEquals('fr', $result['language']);
        $this->assertTrue($result['notifications']['email']);
        $this->assertFalse($result['notifications']['sms']);
    }

    public function testDeleteMultipleSettings(): void
    {
        $companyId = 'company_with_multiple_settings';

        $this->mongoManagerMock->shouldReceive('deleteMany')
            ->with([Common::COMPANY_ID => $companyId])
            ->once();

        $this->settingService->delete($companyId);
    }

    public function testGetSettingsByCompanyIdWithPartialData(): void
    {
        $companyId = 'partial_data_company';
        $partialResult = [
            Common::IDENTIFIER => 'partial_identifier',
            // Pas de TOKEN ni GLOBAL_SETTINGS
        ];

        $this->mongoManagerMock->shouldReceive('getOneBy')
            ->with(
                [Common::COMPANY_ID => $companyId],
                [
                    'projection' => [
                        Common::IDENTIFIER => 1,
                        Common::TOKEN => 1,
                        Common::GLOBAL_SETTINGS => 1,
                        '_id' => 0,
                    ]
                ],
                true
            )
            ->andReturn($partialResult)
            ->once();

        $result = $this->settingService->getSettingsByCompanyId($companyId);

        $this->assertEquals($partialResult, $result);
        $this->assertArrayHasKey(Common::IDENTIFIER, $result);
        $this->assertArrayNotHasKey(Common::TOKEN, $result);
        $this->assertArrayNotHasKey(Common::GLOBAL_SETTINGS, $result);
    }

    /**
     * Test de performance et stress
     */
    public function testSaveWithLargeSettingsArray(): void
    {
        $companyId = 'large_settings_company';
        $largeSettings = [];
        
        // Créer un grand array de settings
        for ($i = 0; $i < 1000; $i++) {
            $largeSettings["setting_$i"] = "value_$i";
        }

        $expectedSettingDocument = array_merge(
            [Common::COMPANY_ID => $companyId],
            $largeSettings,
            [
                Common::IDENTIFIER => null,
                Common::TOKEN => null
            ]
        );

        $this->mongoManagerMock->shouldReceive('getOneBy')
            ->with([Common::COMPANY_ID => $companyId])
            ->andReturn(null)
            ->once();

        $settingMock = Mockery::mock('alias:' . Setting::class);
        $settingMock->shouldReceive('build')
            ->with($companyId, $largeSettings, null, null)
            ->andReturn($expectedSettingDocument)
            ->once();

        $this->mongoManagerMock->shouldReceive('updateWithoutChangeBy')
            ->with([Common::COMPANY_ID => $companyId], Mockery::type('array'))
            ->once();

        $result = $this->settingService->save($companyId, $largeSettings);

        $this->assertArrayHasKey(Common::CREATED_AT, $result);
        $this->assertCount(1003, $result); // 1000 settings + COMPANY_ID + IDENTIFIER + TOKEN + CREATED_AT
    }

    /**
     * Test avec des caractères spéciaux
     */
    public function testSaveWithSpecialCharacters(): void
    {
        $companyId = 'special_chars_éàü_company';
        $specialSettings = [
            'name_français' => 'Café & Thé',
            'address_中文' => '北京市',
            'emoji_🏪' => '🛒💰',
            'json_data' => '{"key": "value with spaces and \"quotes\""}',
            'html_content' => '<div class="test">Content with &lt;tags&gt;</div>'
        ];

        $expectedSettingDocument = array_merge(
            [Common::COMPANY_ID => $companyId],
            $specialSettings,
            [
                Common::IDENTIFIER => null,
                Common::TOKEN => null
            ]
        );

        $this->mongoManagerMock->shouldReceive('getOneBy')
            ->with([Common::COMPANY_ID => $companyId])
            ->andReturn(null)
            ->once();

        $settingMock = Mockery::mock('alias:' . Setting::class);
        $settingMock->shouldReceive('build')
            ->with($companyId, $specialSettings, null, null)
            ->andReturn($expectedSettingDocument)
            ->once();

        $this->mongoManagerMock->shouldReceive('updateWithoutChangeBy')
            ->with([Common::COMPANY_ID => $companyId], Mockery::type('array'))
            ->once();

        $result = $this->settingService->save($companyId, $specialSettings);

        $this->assertArrayHasKey(Common::CREATED_AT, $result);
        $this->assertStringContainsString('Café', $result['name_français']);
        $this->assertStringContainsString('北京市', $result['address_中文']);
        $this->assertStringContainsString('🏪', $result['emoji_🏪']);
    }
}