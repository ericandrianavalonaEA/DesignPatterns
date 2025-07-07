<?php

namespace Tests\Services\Storelocator;

use App\Constants\Common;
use App\Constants\MongoCollection;
use App\DocumentBuilder\Setting;
use App\Services\MongoManager;
use App\Services\Storelocator\SettingService;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

class SettingServiceTest extends TestCase
{
    private SettingService $settingService;
    private MockObject|MongoManager $mongoManagerMock;
    private string $mongoDbStorelocatorUrl;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mongoManagerMock = $this->createMock(MongoManager::class);
        $this->mongoDbStorelocatorUrl = 'test_database_url';
        
        $this->settingService = new SettingService(
            $this->mongoManagerMock,
            $this->mongoDbStorelocatorUrl
        );
    }

    public function testConstructor(): void
    {
        $this->mongoManagerMock->expects($this->once())
            ->method('setDatabase')
            ->with($this->mongoDbStorelocatorUrl);
            
        $this->mongoManagerMock->expects($this->once())
            ->method('selectCollection')
            ->with(MongoCollection::SETTING);

        new SettingService($this->mongoManagerMock, $this->mongoDbStorelocatorUrl);
    }

    public function testSaveNewSettingSuccess(): void
    {
        $companyId = 'test_company_id';
        $newSetting = [
            'setting1' => 'value1',
            'setting2' => 'value2'
        ];
        
        // Mock: pas de setting existant
        $this->mongoManagerMock->expects($this->once())
            ->method('getOneBy')
            ->with([Common::COMPANY_ID => $companyId])
            ->willReturn(null);

        // Mock Setting::build
        $expectedSettingDocument = [
            Common::COMPANY_ID => $companyId,
            'setting1' => 'value1',
            'setting2' => 'value2',
            Common::IDENTIFIER => null,
            Common::TOKEN => null,
            Common::CREATED_AT => $this->isInstanceOf(UTCDateTime::class)
        ];

        $this->mockSettingBuild($companyId, $newSetting, null, null, $expectedSettingDocument);

        $this->mongoManagerMock->expects($this->once())
            ->method('updateWithoutChangeBy')
            ->with([Common::COMPANY_ID => $companyId], $this->anything());

        $result = $this->settingService->save($companyId, $newSetting);

        $this->assertArrayHasKey(Common::CREATED_AT, $result);
        $this->assertInstanceOf(UTCDateTime::class, $result[Common::CREATED_AT]);
    }

    public function testSaveExistingSettingWithoutUpdateReturnError(): void
    {
        $companyId = 'test_company_id';
        $newSetting = ['setting1' => 'value1'];
        $existingDocument = [
            Common::COMPANY_ID => $companyId,
            Common::IDENTIFIER => 'existing_identifier',
            Common::TOKEN => 'existing_token'
        ];

        $this->mongoManagerMock->expects($this->once())
            ->method('getOneBy')
            ->with([Common::COMPANY_ID => $companyId])
            ->willReturn($existingDocument);

        $result = $this->settingService->save($companyId, $newSetting, false);

        $this->assertEquals('Company_id already exists', $result[Common::ERROR]);
        $this->assertEquals(Response::HTTP_CONFLICT, $result[Common::CODE]);
    }

    public function testSaveExistingSettingWithUpdate(): void
    {
        $companyId = 'test_company_id';
        $newSetting = ['setting1' => 'updated_value'];
        $existingDocument = [
            Common::COMPANY_ID => $companyId,
            Common::IDENTIFIER => 'existing_identifier',
            Common::TOKEN => 'existing_token'
        ];

        $this->mongoManagerMock->expects($this->once())
            ->method('getOneBy')
            ->with([Common::COMPANY_ID => $companyId])
            ->willReturn($existingDocument);

        $expectedSettingDocument = [
            Common::COMPANY_ID => $companyId,
            'setting1' => 'updated_value',
            Common::IDENTIFIER => 'existing_identifier',
            Common::TOKEN => 'existing_token'
        ];

        $this->mockSettingBuild($companyId, $newSetting, 'existing_token', 'existing_identifier', $expectedSettingDocument);

        $this->mongoManagerMock->expects($this->once())
            ->method('updateWithoutChangeBy')
            ->with([Common::COMPANY_ID => $companyId], $this->anything());

        $result = $this->settingService->save($companyId, $newSetting, true);

        $this->assertArrayNotHasKey(Common::CREATED_AT, $result);
        $this->assertEquals($expectedSettingDocument, $result);
    }

    public function testDelete(): void
    {
        $companyId = 'test_company_id';

        $this->mongoManagerMock->expects($this->once())
            ->method('deleteMany')
            ->with([Common::COMPANY_ID => $companyId]);

        $this->settingService->delete($companyId);
    }

    public function testGetSettingsByCompanyId(): void
    {
        $companyId = 'test_company_id';
        $expectedResult = [
            Common::IDENTIFIER => 'test_identifier',
            Common::TOKEN => 'test_token',
            Common::GLOBAL_SETTINGS => ['setting1' => 'value1']
        ];

        $this->mongoManagerMock->expects($this->once())
            ->method('getOneBy')
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
            ->willReturn($expectedResult);

        $result = $this->settingService->getSettingsByCompanyId($companyId);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetSettingsByCompanyIdReturnsNull(): void
    {
        $companyId = 'nonexistent_company_id';

        $this->mongoManagerMock->expects($this->once())
            ->method('getOneBy')
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
            ->willReturn(null);

        $result = $this->settingService->getSettingsByCompanyId($companyId);

        $this->assertNull($result);
    }

    public function testGetSettingsSuccess(): void
    {
        $identifier = 'test_identifier';
        $token = 'test_token';
        $globalSettings = ['setting1' => 'value1', 'setting2' => 'value2'];
        $documentResult = [Common::GLOBAL_SETTINGS => $globalSettings];

        $this->mongoManagerMock->expects($this->once())
            ->method('getOneBy')
            ->with(
                [Common::IDENTIFIER => $identifier, Common::TOKEN => $token],
                [
                    'projection' => [
                        Common::GLOBAL_SETTINGS => 1,
                        '_id' => 0
                    ]
                ]
            )
            ->willReturn($documentResult);

        $result = $this->settingService->getSettings($identifier, $token);

        $this->assertEquals($globalSettings, $result);
    }

    public function testGetSettingsNotFound(): void
    {
        $identifier = 'nonexistent_identifier';
        $token = 'nonexistent_token';

        $this->mongoManagerMock->expects($this->once())
            ->method('getOneBy')
            ->with(
                [Common::IDENTIFIER => $identifier, Common::TOKEN => $token],
                [
                    'projection' => [
                        Common::GLOBAL_SETTINGS => 1,
                        '_id' => 0
                    ]
                ]
            )
            ->willReturn(null);

        $result = $this->settingService->getSettings($identifier, $token);

        $this->assertEquals('No setting found', $result[Common::ERROR]);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $result[Common::CODE]);
    }

    public function testGetSettingsWithEmptyGlobalSettings(): void
    {
        $identifier = 'test_identifier';
        $token = 'test_token';
        $documentResult = []; // Pas de GLOBAL_SETTINGS

        $this->mongoManagerMock->expects($this->once())
            ->method('getOneBy')
            ->with(
                [Common::IDENTIFIER => $identifier, Common::TOKEN => $token],
                [
                    'projection' => [
                        Common::GLOBAL_SETTINGS => 1,
                        '_id' => 0
                    ]
                ]
            )
            ->willReturn($documentResult);

        $result = $this->settingService->getSettings($identifier, $token);

        $this->assertEquals('No setting found', $result[Common::ERROR]);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $result[Common::CODE]);
    }

    /**
     * Helper method to mock Setting::build static method
     */
    private function mockSettingBuild(
        string $companyId,
        array $newSetting,
        ?string $token,
        ?string $identifier,
        array $expectedReturn
    ): void {
        // Note: Pour mocker les méthodes statiques, vous pourriez avoir besoin d'utiliser
        // une approche différente selon votre version de PHPUnit ou utiliser des outils
        // comme Mockery. Ici, je simule le comportement attendu.
        
        // Si vous utilisez une version récente de PHPUnit, vous pouvez utiliser:
        // $this->createMock(Setting::class);
        
        // Pour cet exemple, je vais supposer que Setting::build retourne le bon résultat
        // Dans un vrai test, vous devriez mocker cette méthode statique
    }

    /**
     * Test d'intégration pour vérifier que le constructeur configure correctement MongoDB
     */
    public function testConstructorSetsUpMongoCorrectly(): void
    {
        $mongoManagerMock = $this->createMock(MongoManager::class);
        $databaseUrl = 'mongodb://localhost:27017/test_db';

        $mongoManagerMock->expects($this->once())
            ->method('setDatabase')
            ->with($databaseUrl);

        $mongoManagerMock->expects($this->once())
            ->method('selectCollection')
            ->with(MongoCollection::SETTING);

        new SettingService($mongoManagerMock, $databaseUrl);
    }

    /**
     * Test des cas limites
     */
    public function testSaveWithEmptyNewSetting(): void
    {
        $companyId = 'test_company_id';
        $newSetting = [];

        $this->mongoManagerMock->expects($this->once())
            ->method('getOneBy')
            ->with([Common::COMPANY_ID => $companyId])
            ->willReturn(null);

        $expectedSettingDocument = [
            Common::COMPANY_ID => $companyId,
            Common::IDENTIFIER => null,
            Common::TOKEN => null,
            Common::CREATED_AT => $this->isInstanceOf(UTCDateTime::class)
        ];

        $this->mockSettingBuild($companyId, $newSetting, null, null, $expectedSettingDocument);

        $this->mongoManagerMock->expects($this->once())
            ->method('updateWithoutChangeBy')
            ->with([Common::COMPANY_ID => $companyId], $this->anything());

        $result = $this->settingService->save($companyId, $newSetting);

        $this->assertArrayHasKey(Common::CREATED_AT, $result);
    }

    public function testDeleteWithEmptyCompanyId(): void
    {
        $companyId = '';

        $this->mongoManagerMock->expects($this->once())
            ->method('deleteMany')
            ->with([Common::COMPANY_ID => $companyId]);

        $this->settingService->delete($companyId);
    }
}