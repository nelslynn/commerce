<?php

/**
 * @file
 * Definition of Drupal\commerce\Tests\ProductVariationTypeTest
 */

namespace Drupal\commerce_product\Tests;

use Drupal\commerce_product\Entity\ProductVariationType;

/**
 * Ensure the product variation type works correctly.
 *
 * @group commerce
 */
class ProductVariationTypeTest extends CommerceProductTestBase {

  /**
   * Tests whether the default product variation type was created.
   */
  public function testDefaultProductVariationType() {
    $variationType = ProductVariationType::load('default');
    $this->assertTrue($variationType, 'The default product variation type is available.');

    $this->drupalGet('admin/commerce/config/product-variation-types');
    $rows = $this->cssSelect('table tbody tr');
    $this->assertEqual(count($rows), 1, '1 product variation type is correctly listed.');
  }

  /**
   * Tests creating a product variation type programmatically and via a form.
   */
  function testProductVariationTypeCreation() {
    $values = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(),
    ];
    $variationType = $this->createEntity('commerce_product_variation_type', $values);
    $variationType = ProductVariationType::load($values['id']);
    $this->assertEqual($variationType->label(), $values['label'], 'The new product variation type has the correct label.');

    $user = $this->drupalCreateUser(['administer product types']);
    $this->drupalLogin($user);
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(),
    ];
    $this->drupalPostForm('admin/commerce/config/product-variation-types/add', $edit, t('Save'));
    $variationType = ProductVariationType::load($edit['id']);
    $this->assertTrue($variationType, 'The new product variation type has been created.');
    $this->assertEqual($variationType->label(), $edit['label'], 'The new product variation type has the correct label.');
  }

  /**
   * Tests editing a product variation type using the UI.
   */
  function testProductVariationTypeEditing() {
    $edit = [
      'label' => 'Default2',
    ];
    $this->drupalPostForm('admin/commerce/config/product-variation-types/default/edit', $edit, t('Save'));
    $variationType = ProductVariationType::load('default');
    $this->assertEqual($variationType->label(), 'Default2', 'The label of the product variation type has been changed.');
  }

  /**
   * Tests deleting a product variation type via a form.
   */
  public function testProductVariationTypeDeletion() {
    $variationType = $this->createEntity('commerce_product_variation_type', [
      'id' => 'foo',
      'label' => 'foo'
    ]);
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => $variationType->id(),
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
    ]);

    // @todo Make sure $variationType->delete() also does nothing if there's
    // a variation of that type. Right now the check is done on the form level.
    $this->drupalGet('admin/commerce/config/product-variation-types/' . $variationType->id() . '/delete');
    $this->assertRaw(
      t('%type is used by 1 product variation on your site. You can not remove this product variation type until you have removed all of the %type product variations.', ['%type' => $variationType->label()]),
      'The product variation type will not be deleted until all variations of that type are deleted.'
    );
    $this->assertNoText(t('This action cannot be undone.'), 'The product variation type deletion confirmation form is not available');

    $variation->delete();
    $this->drupalGet('admin/commerce/config/product-variation-types/' . $variationType->id() . '/delete');
    $this->assertRaw(
      t('Are you sure you want to delete the product variation type %type?', ['%type' => $variationType->label()]),
      'The product variation type is available for deletion'
    );
    $this->assertText(t('This action cannot be undone.'), 'The product variation type deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $exists = (bool) ProductVariationType::load($variationType->id());
    $this->assertFalse($exists, 'The new product variation type has been deleted from the database.');
  }

}
