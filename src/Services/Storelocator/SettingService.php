<?php

namespace App\Services\Storelocator;

use App\Constants\Common;
use App\Constants\MongoCollection;
use App\DocumentBuilder\Setting;
use App\Services\MongoManager;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\HttpFoundation\Response;

class SettingService
{
    /**
     * @throws \Exception
     */
    public function __construct(
        private readonly MongoManager $mongoManager,
        private readonly string       $mongoDbStorelocatorUrl,
    )
    {
        $this->mongoManager->setDatabase($mongoDbStorelocatorUrl);
        $this->mongoManager->selectCollection(MongoCollection::SETTING);
    }

    public function save(string $companyId, array $newSetting, bool $isUpdate = false): array
    {
        $currentSettingDocument = $this->mongoManager->getOneBy([Common::COMPANY_ID => $companyId]);
        if (!$isUpdate && $currentSettingDocument) {
            return [
                Common::ERROR => 'Company_id already exists',
                Common::CODE => Response::HTTP_CONFLICT,
            ];
        }

        $newSetting[Common::IDENTIFIER] = $currentSettingDocument[Common::IDENTIFIER] ?? null;
        $newSetting[Common::TOKEN] = $currentSettingDocument[Common::TOKEN] ?? null;

        $newSettingDocument = Setting::build(
            $companyId,
            $newSetting,
            $currentSettingDocument[Common::TOKEN] ?? null,
            $currentSettingDocument[Common::IDENTIFIER] ?? null
        );
        if (!$isUpdate) {
            $newSettingDocument[Common::CREATED_AT] = new UTCDateTime();
        }
        $this->mongoManager->updateWithoutChangeBy([Common::COMPANY_ID => $companyId], $newSettingDocument);

        return $newSettingDocument;
    }

    /**
     * @throws \Exception
     */
    public function delete(string $companyId): void
    {
        $this->mongoManager->deleteMany([Common::COMPANY_ID => $companyId]);
    }

    public function getSettingsByCompanyId(string $companyId): ?array
    {
        return $this->mongoManager->getOneBy([Common::COMPANY_ID => $companyId], [
            'projection' => [
                Common::IDENTIFIER => 1,
                Common::TOKEN => 1,
                Common::GLOBAL_SETTINGS => 1,
                '_id' => 0,
            ]
        ],true);
    }

    public function getSettings(string $identifier, string $token): array
    {
        $result = $this->mongoManager->getOneBy(
            [Common::IDENTIFIER => $identifier, Common::TOKEN => $token],
            [
                'projection' => [
                    Common::GLOBAL_SETTINGS => 1,
                    '_id' => 0
                ]
            ]
        )[Common::GLOBAL_SETTINGS] ?? null;
        if (!$result) {
            return [
                Common::ERROR => 'No setting found',
                Common::CODE => Response::HTTP_NOT_FOUND,
            ];
        }

        return $result;
    }
}