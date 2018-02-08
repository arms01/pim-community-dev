Feature: Update product models through XLSX import
  In order to setup my application
  As a product manager
  I need to be able to update existing product models

  Background:
    Given the "catalog_modeling" catalog configuration

  Scenario: Julia successfully updates an exiting root product model through XLSX import
    Given the following root product model:
      | code     | parent | family_variant      | categories | collection | description-en_US-ecommerce | erp_name-en_US | price   | color | variation_name-en_US | composition |
      | code-001 |        | clothing_color_size | master_men | Spring2017 | description                 | Blazers_1654   | 100 EUR |       |                      |             |
    And the following XLSX file to import:
      """
      code;parent;family_variant;categories;collection;description-en_US-ecommerce;erp_name-en_US;price;color;name-en_US;composition;size;ean;sku;weight
      code-001;;clothing_color_size;master_men;Spring2017;A new description;Blazers_1654;50 EUR;;;;;;;
      """
    When I import it via the job "xlsx_catalog_modeling_product_model_import" as "Julia"
    And I wait for this job to finish
    And I wait for the "xlsx_catalog_modeling_product_model_import" job to finish
    Then there should be the following root product model:
      | code     | categories | family_variant      | collection   | description-en_US-ecommerce | erp_name-en_US | price     |
      | code-001 | master_men | clothing_color_size | [Spring2017] | A new description           | Blazers_1654   | 50.00 EUR |

  Scenario: Julia successfully updates an exiting product sub product model through XLSX import
    Given the following root product model:
      | code     | parent   | family_variant      | categories         | collection | description-en_US-ecommerce | erp_name-en_US | price   |
      | code-001 |          | clothing_color_size | master_men         | Spring2017 | description                 | Blazers_1654   | 100 EUR |
    And the following sub product model:
      | code     | parent   | family_variant      | categories         | color | variation_name-en_US | composition |
      | code-002 | code-001 | clothing_color_size | master_men_blazers | blue  | Blazers              | composition |
    And the following XLSX file to import:
      """
      code;parent;family_variant;categories;collection;description-en_US-ecommerce;erp_name-en_US;price;color;variation_name-en_US;composition;size;ean;sku;weight
      code-002;code-001;clothing_color_size;master_men_blazers;;A new description for a sub model;;;blue;Beautiful blazers;composition;;;;
      """
    When I import it via the job "xlsx_catalog_modeling_product_model_import" as "Julia"
    And I wait for this job to finish
    And I wait for the "xlsx_catalog_modeling_product_model_import" job to finish
    Then there should be the following root product model:
      | code     | categories | family_variant      | collection   | description-en_US-ecommerce | erp_name-en_US | price      |
      | code-001 | master_men | clothing_color_size | [Spring2017] | description                 | Blazers_1654   | 100.00 EUR |
    And there should be the following product model:
      | code     | color  | variation_name-en_US | composition |
      | code-002 | [blue] | Beautiful blazers    | composition |

  Scenario: Julia successfully updates exiting product models through XLSX import with comparison enabled
    Given the following root product model:
      | code     | parent   | family_variant      | categories         | collection | description-en_US-ecommerce | erp_name-en_US | price   |
      | code-001 |          | clothing_color_size | master_men         | Spring2017 | description                 | Blazers_1654   | 100 EUR |
    And the following sub product model:
      | code     | parent   | family_variant      | categories         | color | variation_name-en_US | composition |
      | code-002 | code-001 | clothing_color_size | master_men_blazers | blue  | Blazers              | composition |
    And the following XLSX file to import:
      """
      code;parent;family_variant;categories;collection;description-en_US-ecommerce;erp_name-en_US;price;color;variation_name-en_US;composition
      code-001;;clothing_color_size;master_men;Spring2017;a new description;Blazers_1654;50 EUR;;;
      code-002;code-001;clothing_color_size;master_men_blazers;;;;;blue;Beautiful blazers;composition
      """
    When I import it via the job "xlsx_catalog_modeling_product_model_import" as "Julia"
    And I wait for this job to finish
    And I wait for the "xlsx_catalog_modeling_product_model_import" job to finish
    Then there should be the following root product model:
      | code     | categories | family_variant      | collection   | description-en_US-ecommerce | erp_name-en_US | price     |
      | code-001 | master_men | clothing_color_size | [Spring2017] | a new description           | Blazers_1654   | 50.00 EUR |
    And there should be the following product model:
      | code     | color  | variation_name-en_US | composition |
      | code-002 | [blue] | Beautiful blazers    | composition |
