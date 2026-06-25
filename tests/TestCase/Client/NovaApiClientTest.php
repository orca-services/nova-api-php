<?php

namespace OrcaServices\NovaApi\Test\TestCase\Client;

use Cake\Chronos\Chronos;
use OrcaServices\NovaApi\Parameter\NovaCheckSwissPassValidityParameter;
use OrcaServices\NovaApi\Parameter\NovaConfirmReceiptsParameter;
use OrcaServices\NovaApi\Parameter\NovaCreateOffersParameter;
use OrcaServices\NovaApi\Parameter\NovaCreateReceiptsParameter;
use OrcaServices\NovaApi\Parameter\NovaCreateServicesParameter;
use OrcaServices\NovaApi\Parameter\NovaPurchaseServicesParameter;
use OrcaServices\NovaApi\Parameter\NovaSearchPartnerParameter;
use OrcaServices\NovaApi\Parameter\NovaSearchServicesParameter;
use OrcaServices\NovaApi\Test\Response\Client\CheckSwissPassValidityNotOkTestResponse;
use OrcaServices\NovaApi\Test\Response\Client\CheckSwissPassValidityTestResponse;
use OrcaServices\NovaApi\Test\Response\Client\SearchPartnerTestResponse;
use OrcaServices\NovaApi\Test\Traits\NovaClientTestTrait;
use OrcaServices\NovaApi\Test\Traits\UnitTestTrait;
use OrcaServices\NovaApi\Type\GenderType;
use PHPUnit\Framework\TestCase;

/**
 * Tests.
 */
class NovaApiClientTest extends TestCase
{
    use NovaClientTestTrait;
    use UnitTestTrait;

    /**
     * Test.
     *
     * @return void
     */
    public function testSearchPartnerByTkid()
    {
        // Create a mocked response queue
        $responses = [
            new SearchPartnerTestResponse(),
        ];

        $client = $this->createNovaApiClient($responses);

        $parameter = new NovaSearchPartnerParameter();
        $this->setIdentifier($parameter);
        $parameter->tkId = '949e2e6a-fdd1-4f07-8784-201e588ae834';

        $actual = $client->searchPartner($parameter);

        $this->assertEmpty($actual->messages);
        $this->assertNotEmpty($actual->partners);
        $this->assertCount(1, $actual->partners);

        $partner = $actual->partners[0];

        $this->assertSame('949e2e6a-fdd1-4f07-8784-201e588ae834', $partner->tkId);
        $this->assertSame('164-937-314-5', $partner->ckm);
        $this->assertSame('DAW856', $partner->cardNumber);
        $this->assertSame('4133', $partner->postalCode);
        $this->assertSame('CH', $partner->country);
        $this->assertSame('Pratteln', $partner->city);
        $this->assertSame('4133', $partner->postalCode);
        $this->assertNull($partner->additional);
        $this->assertSame('Bahnhofstrasse 1', $partner->street);
        $this->assertSame('1234', $partner->poBox);
        $this->assertSame('+41612330975', $partner->phoneNumber);
        $this->assertSame('+41792330976', $partner->mobileNumber);
        $this->assertSame('max.mustermann@example.com', $partner->email);
        $this->assertSame('Mustermann', $partner->firstName); // should be the lastName
        $this->assertSame('Max', $partner->lastName); // should be the firstName
        $this->assertSame(
            '1982-03-28 00:00:00',
            $partner->dateOfBirth ? $partner->dateOfBirth->toDateTimeString() : ''
        );
        $this->assertSame(1, $partner->genderTypeId);
        $this->assertSame('2019-09-02 08:13:28', $partner->changedAt ? $partner->changedAt->toDateTimeString() : '');
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testSearchPartnerByCardNumber()
    {
        // Create a mocked response queue
        $responses = [
            new SearchPartnerTestResponse(),
        ];

        $client = $this->createNovaApiClient($responses);

        $parameter = new NovaSearchPartnerParameter();
        $this->setIdentifier($parameter);
        $parameter->cardNumber = 'DAW856';

        $actual = $client->searchPartner($parameter);

        $this->assertEmpty($actual->messages);
        $this->assertNotEmpty($actual->partners);
        $this->assertCount(1, $actual->partners);

        $partner = $actual->partners[0];

        $this->assertSame('949e2e6a-fdd1-4f07-8784-201e588ae834', $partner->tkId);
        $this->assertSame('164-937-314-5', $partner->ckm);
        $this->assertSame('DAW856', $partner->cardNumber);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testSearchPartnerByPassengerInformation()
    {
        // Create a mocked response queue
        $responses = [
            new SearchPartnerTestResponse(),
        ];

        $client = $this->createNovaApiClient($responses);

        $parameter = new NovaSearchPartnerParameter();
        $this->setIdentifier($parameter);
        $parameter->firstName = 'Mustermann';
        $parameter->lastName = 'Max';
        $parameter->mail = 'max.mustermann@example.com';
        $parameter->country = 'CH';
        $parameter->city = 'Pratteln';
        $parameter->postalCode = '4133';
        $parameter->street = 'Bahnhofstrasse 1';
        $parameter->dateOfBirth = Chronos::parse('1982-03-28');

        $actual = $client->searchPartner($parameter);

        $this->assertEmpty($actual->messages);
        $this->assertNotEmpty($actual->partners);
        $this->assertCount(1, $actual->partners);

        $partner = $actual->partners[0];

        $this->assertSame('949e2e6a-fdd1-4f07-8784-201e588ae834', $partner->tkId);
        $this->assertSame('164-937-314-5', $partner->ckm);
        $this->assertSame('DAW856', $partner->cardNumber);
    }

    /**
     * Test.
     *
     * @dataProvider checkSwissPassValidityProvider
     *
     * @param string $tkId The tkId
     * @param object $response The response
     * @param string $status The status
     * @param string $result The result
     * @param int $messageCount The number of messages
     *
     * @return void
     */
    public function testCheckSwissPassValidity(
        string $tkId,
        $response,
        string $status,
        string $result,
        int $messageCount
    ) {
        // Create a mocked response queue
        $responses = [
            $response,
        ];

        $client = $this->createNovaApiClient($responses);

        $parameter = new NovaCheckSwissPassValidityParameter();
        $this->setIdentifier($parameter);

        $parameter->tkId = $tkId;

        $actual = $client->checkSwissPassValidity($parameter);

        $this->assertSame($actual->status, $status);
        $this->assertSame($actual->result, $result);
        $this->assertCount($messageCount, $actual->messages);
    }

    /**
     * Data provider.
     *
     * @return array The data
     */
    public function checkSwissPassValidityProvider(): array
    {
        return [
            'OK' => [
                // Max Mustermann with valid SwissPass
                '949e2e6a-fdd1-4f07-8784-201e588ae834',
                new CheckSwissPassValidityTestResponse(),
                'OK',
                'SP_OK',
                0,
            ],
            'SP_NICHT_OK_FOTO_NICHT_OK' => [
                // Hans Meier, with pobox and email
                '05cd0051-649e-4c0e-a54e-3e5e0596f8dc',
                new CheckSwissPassValidityNotOkTestResponse(),
                'OK',
                'SP_NICHT_OK_FOTO_NICHT_OK',
                1,
            ],
        ];
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateOffersClass2()
    {
        // Create a mocked response queue
        $responses = $this->createResponses(
            [
                __DIR__ . '/../../Resources/Response/Client/CreateOffersResponse.xml',
            ]
        );

        $client = $this->createNovaApiClient($responses);

        $parameter = new NovaCreateOffersParameter();
        $this->setIdentifier($parameter);
        $parameter->tkId = '949e2e6a-fdd1-4f07-8784-201e588ae834';
        $parameter->novaProductNumber = 51648;
        $parameter->dateOfBirth = Chronos::createFromDate(1982, 03, 28);
        $parameter->genderTypeId = GenderType::MEN;
        $parameter->travelClass = 2;
        $parameter->validFrom = Chronos::now()->setTime(0, 0);

        $actual = $client->createOffers($parameter);

        $this->assertCount(1, $actual->offers);

        $offer = $actual->offers[0];

        $this->assertSame('_5c63dc7d-62e5-4f3a-a761-464488e92000', $offer->novaOfferId);
        $this->assertSame('105.00', $offer->price);
        $this->assertSame('CHF', $offer->currency);
        $this->assertSame('51648', $offer->productNumber);
        $this->assertSame('Alle Zonen, Erwachsene, Monate', $offer->title);
        $this->assertSame('2019-09-01 00:00:00', $offer->validFrom->toDateTimeString());
        $this->assertSame('2019-09-30 23:59:59', $offer->validTo->toDateTimeString());
        $this->assertSame('SWISSPASS', $offer->carrierMedium);
        $this->assertSame('KLASSE_2', $offer->travelClass);

        $this->assertCount(1, $actual->messages);
        $this->assertSame(
            [
                'id' => 'M0',
                'code' => '33098',
                'timestamp' => '2019-09-05T13:40:28.000+02:00',
                'type' => 'WARNUNG',
                'customerRelevant' => 'false',
                'message' => 'Der Reisende 1 erhält kein SwissPass-Angebot, weil er bereits einen ' .
                    'gültigen SwissPass, oder einen laufenden Kartenauftrag hat.',
            ],
            (array)$actual->messages[0]
        );
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateService()
    {
        // Create a mocked response queue
        $responses = $this->createResponses(
            [
                __DIR__ . '/../../Resources/Response/Client/CreateServiceResponse.xml',
            ]
        );

        $client = $this->createNovaApiClient($responses);

        $parameter = new NovaCreateServicesParameter();
        $this->setIdentifier($parameter);
        $parameter->tkId = '949e2e6a-fdd1-4f07-8784-201e588ae834';
        $parameter->novaOfferId = '_5c63dc7d-62e5-4f3a-a761-464488e92000';

        $actual = $client->createService($parameter);

        $this->assertCount(1, $actual->services);
        $this->assertSame('OFFERIERT', $actual->services[0]->serviceStatus);

        $service = (array)$actual->services[0];

        $this->assertSame(
            [
                'tkId' => '949e2e6a-fdd1-4f07-8784-201e588ae834',
                'serviceId' => '15900011821804',
                'serviceStatus' => 'OFFERIERT',
                'serviceReference' => '11821804',
                'productNumber' => '51648',
                'price' => '105.00',
                'currency' => 'CHF',
                'vatAmount' => '105.00',
                'vatPercent' => '7.70',
            ],
            $service
        );

        $this->assertEmpty($actual->messages);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testPurchaseService()
    {
        // Create a mocked response queue
        $responses = $this->createResponses(
            [
                __DIR__ . '/../../Resources/Response/Client/PurchaseServiceResponse.xml',
            ]
        );

        $client = $this->createNovaApiClient($responses);

        $parameter = new NovaPurchaseServicesParameter();
        $this->setIdentifier($parameter);
        $parameter->novaServiceId = '15900011821804';
        $parameter->price = 105.00;
        $parameter->currency = 'CHF';
        $parameter->paymentTypeCode = 'BAR';

        $actual = $client->purchaseService($parameter);

        $this->assertCount(1, $actual->services);
        $this->assertSame('VERKAUFT', $actual->services[0]->serviceStatus);

        $service = (array)$actual->services[0];

        $this->assertSame(
            [
                'tkId' => '949e2e6a-fdd1-4f07-8784-201e588ae834',
                'serviceId' => '15900011821804',
                'serviceStatus' => 'VERKAUFT',
                'serviceReference' => '11821804',
                'productNumber' => '51648',
                'price' => '105.00',
                'currency' => 'CHF',
                'vatAmount' => '105.00',
                'vatPercent' => '7.70',
            ],
            $service
        );

        $this->assertEmpty($actual->messages);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateReceipt()
    {
        // Create a mocked response queue
        $responses = $this->createResponses(
            [
                __DIR__ . '/../../Resources/Response/Client/CreateReceiptsResponse.xml',
            ]
        );

        $client = $this->createNovaApiClient($responses);

        $parameter = new NovaCreateReceiptsParameter();
        $this->setIdentifier($parameter);
        $parameter->novaServiceId = '15900011821804';

        $actual = $client->createReceipt($parameter);

        $this->assertCount(1, $actual->services);
        $this->assertSame('PRODUKTION_BEREIT', $actual->services[0]->serviceStatus);

        $service = (array)$actual->services[0];

        $this->assertSame(
            [
                'tkId' => '949e2e6a-fdd1-4f07-8784-201e588ae834',
                'serviceId' => '15900011821804',
                'serviceStatus' => 'PRODUKTION_BEREIT',
                'serviceReference' => '11821804',
                'productNumber' => '51648',
                'price' => '105.00',
                'currency' => 'CHF',
                'vatAmount' => '105.00',
                'vatPercent' => '7.70',
            ],
            $service
        );

        $this->assertEmpty($actual->messages);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testConfirmReceipt()
    {
        // Create a mocked response queue
        $responses = $this->createResponses(
            [
                __DIR__ . '/../../Resources/Response/Client/ConfirmReceiptResponse.xml',
            ]
        );

        $client = $this->createNovaApiClient($responses);

        $parameter = new NovaConfirmReceiptsParameter();
        $this->setIdentifier($parameter);
        $parameter->novaServiceId = '15900011821804';

        $actual = $client->confirmReceipt($parameter);

        $this->assertCount(1, $actual->services);
        $this->assertSame('PRODUKTION_ERFOLGREICH', $actual->services[0]->serviceStatus);

        $service = (array)$actual->services[0];

        $this->assertSame(
            [
                'tkId' => '949e2e6a-fdd1-4f07-8784-201e588ae834',
                'serviceId' => '15900011821804',
                'serviceStatus' => 'PRODUKTION_ERFOLGREICH',
                'serviceReference' => '11821804',
                'productNumber' => '51648',
                'price' => '105.00',
                'currency' => 'CHF',
                'vatAmount' => '105.00',
                'vatPercent' => '7.70',
            ],
            $service
        );

        $this->assertEmpty($actual->messages);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testSearchServicesByTkId()
    {
        // Create a mocked response queue
        $responses = $this->createResponses(
            [
                __DIR__ . '/../../Resources/Response/Client/SearchServicesResponse.xml',
            ]
        );

        $client = $this->createNovaApiClient($responses);

        $parameter = new NovaSearchServicesParameter();
        $this->setIdentifier($parameter);
        $parameter->tkId = '949e2e6a-fdd1-4f07-8784-201e588ae834';
        $parameter->periodOfUseStart = Chronos::now()->subDays(1);
        $parameter->periodOfUseEnd = Chronos::now()->addDays(1);

        $actual = $client->searchServices($parameter);

        $this->assertCount(19, $actual->services);
        $this->assertEquals('949e2e6a-fdd1-4f07-8784-201e588ae834', $actual->services[0]->tkId);
        $this->assertEquals('2019-09-01 00:00:00', $actual->services[0]->validFrom->toDateTimeString());
        $this->assertEquals('2019-10-01 05:00:00', $actual->services[0]->validTo->toDateTimeString());
        $this->assertEquals('51648', $actual->services[0]->productNumber);
        $this->assertEquals(['100', '123'], $actual->services[0]->zones);
        $this->assertEquals([], $actual->services[1]->zones);
    }
}
