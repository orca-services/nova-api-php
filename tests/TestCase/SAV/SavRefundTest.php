<?php

namespace OrcaServices\NovaApi\Test\TestCase\SAV;

use OrcaServices\NovaApi\Method\NovaConfirmReceiptsMethod;
use OrcaServices\NovaApi\Method\NovaCreateReceiptsMethod;
use OrcaServices\NovaApi\Method\NovaCreateServicesMethod;
use OrcaServices\NovaApi\Method\NovaPurchaseServicesMethod;
use OrcaServices\NovaApi\Method\NovaSavCreateOffersMethod;
use OrcaServices\NovaApi\Method\NovaSearchServicesMethod;
use OrcaServices\NovaApi\Parameter\NovaConfirmReceiptsParameter;
use OrcaServices\NovaApi\Parameter\NovaCreateReceiptsParameter;
use OrcaServices\NovaApi\Parameter\NovaCreateServicesParameter;
use OrcaServices\NovaApi\Parameter\NovaPurchaseServicesParameter;
use OrcaServices\NovaApi\Parameter\NovaSavCreateOffersParameter;
use OrcaServices\NovaApi\Parameter\NovaSearchServicesParameter;
use OrcaServices\NovaApi\Test\Response\SAV\CreateSavOffersTestResponse;
use OrcaServices\NovaApi\Test\Response\SAV\SavConfirmReceiptTestResponse;
use OrcaServices\NovaApi\Test\Response\SAV\SavCreateReceiptsTestResponse;
use OrcaServices\NovaApi\Test\Response\SAV\SavCreateServiceTestResponse;
use OrcaServices\NovaApi\Test\Response\SAV\SavPurchaseServiceTestResponse;
use OrcaServices\NovaApi\Test\Response\SAV\SavSearchServicesTestResponse;
use OrcaServices\NovaApi\Test\Traits\NovaClientTestTrait;
use OrcaServices\NovaApi\Test\Traits\UnitTestTrait;
use OrcaServices\NovaApi\Type\NovaSavReasonType;
use PHPUnit\Framework\TestCase;

/**
 * Tests.
 */
class SavRefundTest extends TestCase
{
    use NovaClientTestTrait;
    use UnitTestTrait;

    /**
     * Test.
     *
     * @return void
     */
    public function testSavFullRefund()
    {
        // Create a mocked response queue
        $responses = [
            new SavSearchServicesTestResponse(),
            new CreateSavOffersTestResponse(),
            new SavCreateServiceTestResponse(),
            new SavPurchaseServiceTestResponse(),
            new SavCreateReceiptsTestResponse(),
            new SavConfirmReceiptTestResponse(),
        ];

        $this->createNovaApiClient($responses);

        // TKID: 7f80a2ab-23b7-4903-8811-4800aa5a6845
        // ProduktBezeichnung: TNW U-Abo Monatsabo persönlich Auswärtige
        // Valid from: 2021-05-15
        $serviceId = '15900020445739';

        // Step 1: Identify the service
        $novaSearchServicesParameter = new NovaSearchServicesParameter();
        $this->setIdentifier($novaSearchServicesParameter);
        $novaSearchServicesParameter->serviceId = $serviceId;

        $searchServiceMethod = $this->container->get(NovaSearchServicesMethod::class);
        $searchServiceResult = $searchServiceMethod->searchServices($novaSearchServicesParameter);

        $this->assertNotEmpty($searchServiceResult->services);
        $this->assertEmpty($searchServiceResult->messages);

        // Step 2: Create refund offer
        $parameter = new NovaSavCreateOffersParameter();
        $this->setIdentifier($parameter);
        $parameter->serviceId = $serviceId;
        $parameter->reason = NovaSavReasonType::RETURN_BEFORE_1VALIDITY;

        $method = $this->container->get(NovaSavCreateOffersMethod::class);
        $createSavOffersResult = $method->createSavOffers($parameter);

        $this->assertSame([], $createSavOffersResult->messages);
        $this->assertNotEmpty($createSavOffersResult->offers[0]->novaOfferId);

        // Step 3: Create
        $parameter = new NovaCreateServicesParameter();
        $this->setIdentifier($parameter);
        $parameter->novaOfferId = $createSavOffersResult->offers[0]->novaOfferId;

        // Don't set TKID for this process
        // $parameter->tkId = $savOffers->offers[0]->tkId;

        $parameter->firstName = 'John';
        $parameter->lastName = 'Doe';
        $parameter->country = 'CH';
        $parameter->postalCode = '4000';

        $method = $this->container->get(NovaCreateServicesMethod::class);
        $createServiceResult = $method->createService($parameter);

        $this->assertSame([], $createServiceResult->messages);
        $this->assertCount(1, $createServiceResult->services);
        $this->assertSame('OFFERIERT', $createServiceResult->services[0]->serviceStatus);
        $this->assertSame('15900020446658', $createServiceResult->services[0]->serviceId);
        $this->assertSame('-105.00', $createServiceResult->services[0]->price);
        $this->assertSame('80026', $createServiceResult->services[0]->productNumber);

        // Step 4: purchase service
        $parameter = new NovaPurchaseServicesParameter();
        $this->setIdentifier($parameter);
        $parameter->novaServiceId = $createServiceResult->services[0]->serviceId;
        $parameter->price = (float)$createServiceResult->services[0]->price;
        $parameter->currency = $createServiceResult->services[0]->currency;

        $method = $this->container->get(NovaPurchaseServicesMethod::class);
        $purchaseServiceResult = $method->purchaseService($parameter);

        $this->assertSame([], $purchaseServiceResult->messages);

        $this->assertSame('VERKAUFT', $purchaseServiceResult->services[0]->serviceStatus);

        // Step 5 - create receipts
        $parameter = new NovaCreateReceiptsParameter();
        $this->setIdentifier($parameter);
        $parameter->novaServiceId = $purchaseServiceResult->services[0]->serviceId;

        $method = $this->container->get(NovaCreateReceiptsMethod::class);
        $receiptsResult = $method->createReceipts($parameter);

        $this->assertSame('PRODUKTION_BEREIT', $receiptsResult->services[0]->serviceStatus);

        // Step 6 - confirm receipt production
        $parameter = new NovaConfirmReceiptsParameter();
        $this->setIdentifier($parameter);
        $parameter->novaServiceId = $receiptsResult->services[0]->serviceId;

        $method = $this->container->get(NovaConfirmReceiptsMethod::class);
        $confirmReceiptsResult = $method->confirmReceipts($parameter);

        $this->assertSame('PRODUKTION_ERFOLGREICH', $confirmReceiptsResult->services[0]->serviceStatus);
    }
}
