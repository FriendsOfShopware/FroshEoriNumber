<?php declare(strict_types=1);

namespace Frosh\EoriNumber\Core\Checkout\Cart\Subscriber;

use Frosh\EoriNumber\FroshEoriNumber;
use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EoriNumberCartConverterSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;
    private RequestStack $requestStack;
    private EntityRepositoryInterface $customerRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        RequestStack $requestStack,
        EntityRepositoryInterface $customerRepository
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->requestStack = $requestStack;
        $this->customerRepository = $customerRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartConvertedEvent::class => 'processEoriNumber',
        ];
    }

    public function processEoriNumber(CartConvertedEvent $event): void
    {
        if (!$event->getConversionContext()->shouldIncludeCustomer()) {
            return;
        }

        $customer = $event->getSalesChannelContext()->getCustomer();
        if ($customer === null) {
            return;
        }

        $orderData = $event->getConvertedCart();
        if (!$this->shouldUseEoriNumber($customer)) {
            unset($orderData['orderCustomer']['customFields'][FroshEoriNumber::CUSTOM_FIELD_NAME_EORI_NUMBER]);
        } else {
            // Make sure that the customer custom fields always exist
            $orderData['orderCustomer']['customFields'] ??= [];

            $eoriNumber = $this->getEoriNumber();
            if ($eoriNumber === null) {
                unset($orderData['orderCustomer']['customFields'][FroshEoriNumber::CUSTOM_FIELD_NAME_EORI_NUMBER]);
            } else {
                $orderData['orderCustomer']['customFields'][FroshEoriNumber::CUSTOM_FIELD_NAME_EORI_NUMBER] = $eoriNumber;

                $this->updateCustomerEoriNumber($customer, $eoriNumber, $event->getContext());
            }
        }

        $event->setConvertedCart($orderData);
    }

    private function shouldUseEoriNumber(CustomerEntity $customer): bool
    {
        $shippingAddress = $customer->getActiveShippingAddress();
        if ($shippingAddress === null) {
            return false;
        }

        $activeCountries = $this->systemConfigService->get('FroshEoriNumber.config.activeCountries');
        if (!\is_array($activeCountries) || count($activeCountries) === 0) {
            return false;
        }

        if (!\in_array($shippingAddress->getCountryId(), $activeCountries, true)) {
            return false;
        }

        return true;
    }

    /**
     * @return ?non-empty-string
     */
    private function getEoriNumber(): ?string
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest === null) {
            return null;
        }

        $eoriNumber = \trim((string) $currentRequest->request->get('froshEoriNumber'));
        if ($eoriNumber === '') {
            return null;
        }

        return $eoriNumber;
    }

    /**
     * @param non-empty-string $eoriNumber
     */
    private function updateCustomerEoriNumber(CustomerEntity $customer, string $eoriNumber, Context $context): void
    {
        $customerData = [
            'id' => $customer->getId(),
            'customFields' => [FroshEoriNumber::CUSTOM_FIELD_NAME_EORI_NUMBER => $eoriNumber],
        ];

        $this->customerRepository->update([$customerData], $context);
    }
}
