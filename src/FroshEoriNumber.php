<?php declare(strict_types=1);

namespace Frosh\EoriNumber;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class FroshEoriNumber extends Plugin
{
    public const CUSTOM_FIELD_SET_NAME_EORI_NUMBER = 'frosh_eori_number';
    public const CUSTOM_FIELD_NAME_EORI_NUMBER = 'frosh_eori_number';

    private const CUSTOM_FIELD_SET_EROI_ID = '167acd6698db4ee6a3adfa7c288dd732';
    private const CUSTOM_FIELD_EROI_ID = 'f21814c5a3044aa2848e8bdf8a8cc4d0';

    public function install(InstallContext $installContext): void
    {
        $this->createCustomFields($installContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->removeCustomFields($uninstallContext->getContext());
    }

    private function createCustomFields(Context $context): void
    {
        /** @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $customFieldSetRepository->upsert([
            [
                'id' => self::CUSTOM_FIELD_SET_EROI_ID,
                'name' => self::CUSTOM_FIELD_SET_NAME_EORI_NUMBER,
                'config' => [
                    'label' => [
                        'en-GB' => 'EORI number',
                        'de-DE' => 'EORI Nummer',
                    ],
                ],
                'relations' => [
                    ['entityName' => CustomerDefinition::ENTITY_NAME],
                ],
                'customFields' => [
                    [
                        'id' => self::CUSTOM_FIELD_EROI_ID,
                        'name' => self::CUSTOM_FIELD_NAME_EORI_NUMBER,
                        'type' => CustomFieldTypes::TEXT,
                        'config' => [
                            'componentName' => 'sw-field',
                            'type' => CustomFieldTypes::TEXT,
                            'customFieldType' => CustomFieldTypes::TEXT,
                            'customFieldPosition' => 1,
                            'label' => [
                                'en-GB' => 'EORI number',
                                'de-DE' => 'EORI Nummer',
                            ],
                        ],
                    ],
                ],
            ],
        ], $context);
    }

    private function removeCustomFields(Context $context): void
    {
        /** @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $customFieldSetRepository->delete([['id' => self::CUSTOM_FIELD_SET_EROI_ID]], $context);
    }
}
