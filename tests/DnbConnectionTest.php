<?php

/**
 * @file
 * Provides tests for DnbConnection.
 */

namespace dnb_api\Tests;

use DnbConnection;
use \PHPUnit\Framework\TestCase;

/**
 * Class DnbConnectionTest.
 *
 * @package dnb_api\Tests
 */
class DnbConnectionTest extends TestCase {

  /**
   * Tests the RhcCompany::__construct() method.
   */
  public function testDnbConnection() {
    $dnb = DnbConnection::create();

    // DUNS Number 362179884.
    $actual_response = $dnb->query('data/duns/362179884?productId=cmplnk&versionId=v1');
    $response = json_decode($actual_response);
    $tid = $response->transactionDetail->transactionID;
    $time = $response->transactionDetail->transactionTimestamp;
    $example_json = <<<JSON
{
  "transactionDetail": {
    "transactionID": $tid,
    "transactionTimestamp": $time,
    "inLanguage": "en-US",
    "productID": "cmplnk",
    "productVersion": "1"
  },
  "inquiryDetail": {
    "duns": "362179884",
    "productID": "cmplnk",
    "productVersion": "v1"
  },
  "organization": {
    "duns": "362179884",
    "dunsControlStatus": {
      "operatingStatus": {
        "description": "Active",
        "dnbCode": 9074
      },
      "isMarketable": true,
      "isMailUndeliverable": false,
      "isTelephoneDisconnected": false,
      "isDelisted": false,
      "subjectHandlingDetails": [],
      "fullReportDate": "2014-06-05"
    },
    "primaryName": "US Test Company 47",
    "tradeStyleNames": [
      {
        "name": "US FICTITIOUS COMPANY 47",
        "priority": 1
      }
    ],
    "telephone": [
      {
        "telephoneNumber": "6108820001",
        "isdCode": "1",
        "isUnreachable": false
      }
    ],
    "fax": [],
    "primaryAddress": {
      "language": {},
      "addressCountry": {
        "name": "United States",
        "isoAlpha2Code": "US"
      },
      "continentalRegion": {
        "name": "North America"
      },
      "addressLocality": {
        "name": "BETHLEHEM"
      },
      "minorTownName": null,
      "addressRegion": {
        "name": "Pennsylvania",
        "abbreviatedName": "PA"
      },
      "addressCounty": {
        "name": "LEHIGH"
      },
      "postalCode": "180250001",
      "postalCodePosition": {},
      "streetNumber": null,
      "streetName": null,
      "streetAddress": {
        "line1": "899 EATON AVE",
        "line2": null
      },
      "postOfficeBox": {},
      "isRegisteredAddress": false
    },
    "registeredAddress": {},
    "mailingAddress": {},
    "registrationNumbers": [],
    "industryCodes": [
      {
        "code": "212322",
        "description": "Industrial Sand Mining",
        "typeDescription": "North American Industry Classification System 2012",
        "typeDnBCode": 24664,
        "priority": 1
      },
      {
        "code": "1934",
        "description": "Nonmetallic Mineral Mining & Quarrying",
        "typeDescription": "D&B Hoovers Industry Code",
        "typeDnBCode": 25838,
        "priority": 1
      },
      {
        "code": "1446",
        "description": "Industrial sand mining",
        "typeDescription": "US Standard Industry Code 1987 - 4 digit",
        "typeDnBCode": 399,
        "priority": 1
      },
      {
        "code": "14469903",
        "description": "Enamel sand mining",
        "typeDescription": "D&B Standard Industry Code",
        "typeDnBCode": 3599,
        "priority": 1
      },
      {
        "code": "B",
        "description": "Mining",
        "typeDescription": "D&B Standard Major Industry Code",
        "typeDnBCode": 24657,
        "priority": 1
      }
    ],
    "businessEntityType": {
      "description": "Corporation",
      "dnbCode": 451
    },
    "controlOwnershipDate": "2000",
    "isAgent": null,
    "isImporter": null,
    "isExporter": null,
    "numberOfEmployees": [
      {
        "value": 1,
        "informationScopeDescription": "Consolidated",
        "informationScopeDnBCode": 9067,
        "reliabilityDescription": "Modelled",
        "reliabilityDnBCode": 9094,
        "employeeCategories": []
      },
      {
        "value": 1,
        "informationScopeDescription": "Individual",
        "informationScopeDnBCode": 9066,
        "reliabilityDescription": "Modelled",
        "reliabilityDnBCode": 9094,
        "employeeCategories": []
      }
    ],
    "financials": [
      {
        "financialStatementToDate": null,
        "financialStatementDuration": null,
        "informationScopeDescription": null,
        "informationScopeDnBCode": null,
        "reliabilityDescription": "Modelled",
        "reliabilityDnBCode": 9094,
        "unitCode": "Single Units",
        "yearlyRevenue": [
          {
            "value": 58220,
            "currency": "USD"
          }
        ]
      }
    ],
    "mostSeniorPrincipals": [
      {
        "fullName": "Jayne DOE",
        "jobTitles": [
          {
            "title": "Manager"
          }
        ]
      }
    ],
    "isStandalone": true,
    "corporateLinkage": {}
  }
}
JSON;

    $this->assertNotEmpty($actual_response, 'DnbConnection not made.');
    $this->assertObjectNotHasAttribute('error', $actual_response);
    $this->assertObjectHasAttribute('transactionDetail', $actual_response, '');
    $this->assertEquals($example_json, $actual_response);
  }
}
