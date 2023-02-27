<?php declare(strict_types=1);

namespace Frosh\EoriNumber\Core\Checkout\Order\Validation;

use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidationFactoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderValidationFactory implements DataValidationFactoryInterface
{
    private DataValidationFactoryInterface $coreOrderValidationFactory;
    private SystemConfigService $systemConfigService;

    public function __construct(
        DataValidationFactoryInterface $coreOrderValidationFactory,
        SystemConfigService $systemConfigService
    ) {
        $this->coreOrderValidationFactory = $coreOrderValidationFactory;
        $this->systemConfigService = $systemConfigService;
    }

    public function create(SalesChannelContext $context): DataValidationDefinition
    {
        $definition = $this->coreOrderValidationFactory->create($context);

        $this->addEoriNumberValidation($definition, $context);

        return $definition;
    }

    public function update(SalesChannelContext $context): DataValidationDefinition
    {
        $definition = $this->coreOrderValidationFactory->update($context);

        $this->addEoriNumberValidation($definition, $context);

        return $definition;
    }

    private function addEoriNumberValidation(
        DataValidationDefinition $definition,
        SalesChannelContext $context
    ): void {
        $requiredCountries = $this->systemConfigService->get(
            'FroshEoriNumber.config.requiredCountries',
            $context->getSalesChannelId()
        );
        if (!\is_array($requiredCountries) || count($requiredCountries) === 0) {
            return;
        }

        $countryId = $context->getShippingLocation()->getCountry()->getId();
        if (!\in_array($countryId, $requiredCountries, true)) {
            return;
        }

        $definition->add('froshEoriNumber', new NotBlank());
    }
}
