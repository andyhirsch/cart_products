<?php

namespace Extcode\CartProducts\Tests\Unit\Domain\Model\Product;

use Extcode\CartProducts\Domain\Model\Product\BeVariant;
use Extcode\CartProducts\Domain\Model\Product\BeVariantAttribute;
use Extcode\CartProducts\Domain\Model\Product\Product;
use Extcode\CartProducts\Domain\Model\Product\SpecialPrice;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ProductTest extends UnitTestCase
{
    /**
     * @var Product
     */
    protected $product;

    protected function setUp(): void
    {
        $this->product = new Product();
    }

    protected function tearDown(): void
    {
        unset($this->product);
    }

    /**
     * DataProvider for best Special Price calculation
     *
     * @return array
     */
    public function bestSpecialPriceProvider()
    {
        return [
            [100.0, 80.0, 75.0, 90.0, 75.0],
            [100.0, 75.0, 90.0, 50.0, 50.0],
            [100.0, 80.0, 60.0, 80.0, 60.0],
        ];
    }

    /**
     * DataProvider for best Special Price Discount calculation
     *
     * @return array
     */
    public function bestSpecialPriceDiscountProvider()
    {
        return [
            [100.0, 80.0, 75.0, 90.0, 25.0],
            [100.0, 75.0, 90.0, 50.0, 50.0],
            [100.0, 80.0, 60.0, 80.0, 40.0],
        ];
    }

    /**
     * @test
     */
    public function getProductTypeReturnsInitialValueForProductType()
    {
        $this->assertSame(
            'simple',
            $this->product->getProductType()
        );
    }

    /**
     * @test
     */
    public function setProductTypeSetsProductType()
    {
        $this->product->setProductType('configurable');

        $this->assertSame(
            'configurable',
            $this->product->getProductType()
        );
    }

    /**
     * @test
     */
    public function getTeaserReturnsInitialValueForTeaser()
    {
        $this->assertSame(
            '',
            $this->product->getTeaser()
        );
    }

    /**
     * @test
     */
    public function setTeaserForStringSetsTeaser()
    {
        $this->product->setTeaser('Conceived at T3CON10');

        $this->assertSame(
            'Conceived at T3CON10',
            $this->product->getTeaser()
        );
    }

    /**
     * @test
     */
    public function getMinNumberInOrderInitiallyReturnsMinNumberInOrder()
    {
        $this->assertSame(
            0,
            $this->product->getMinNumberInOrder()
        );
    }

    /**
     * @test
     */
    public function setNegativeMinNumberThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $minNumber = -10;

        $this->product->setMinNumberInOrder($minNumber);
    }

    /**
     * @test
     */
    public function setMinNumberGreaterThanMaxNumberThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $minNumber = 10;

        $this->product->setMinNumberInOrder($minNumber);
    }

    /**
     * @test
     */
    public function setMinNumberInOrderSetsMinNumberInOrder()
    {
        $minNumber = 10;

        $this->product->setMaxNumberInOrder($minNumber);
        $this->product->setMinNumberInOrder($minNumber);

        $this->assertSame(
            $minNumber,
            $this->product->getMinNumberInOrder()
        );
    }

    /**
     * @test
     */
    public function getMaxNumberInOrderInitiallyReturnsMaxNumberInOrder()
    {
        $this->assertSame(
            0,
            $this->product->getMaxNumberInOrder()
        );
    }

    /**
     * @test
     */
    public function setNegativeMaxNumberThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $maxNumber = -10;

        $this->product->setMaxNumberInOrder($maxNumber);
    }

    /**
     * @test
     */
    public function setMaxNumberInOrderSetsMaxNumberInOrder()
    {
        $maxNumber = 10;

        $this->product->setMaxNumberInOrder($maxNumber);

        $this->assertSame(
            $maxNumber,
            $this->product->getMaxNumberInOrder()
        );
    }

    /**
     * @test
     */
    public function setMaxNumberLesserThanMinNumberThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $minNumber = 10;
        $maxNumber = 1;

        $this->product->setMaxNumberInOrder($minNumber);
        $this->product->setMinNumberInOrder($minNumber);

        $this->product->setMaxNumberInOrder($maxNumber);
    }

    /**
     * @test
     */
    public function getPriceReturnsInitialValueForFloat()
    {
        $this->assertSame(
            0.0,
            $this->product->getPrice()
        );
    }

    /**
     * @test
     */
    public function setPriceSetsPrice()
    {
        $this->product->setPrice(3.14159265);

        $this->assertSame(
            3.14159265,
            $this->product->getPrice()
        );
    }

    /**
     * @test
     */
    public function getSpecialPricesInitiallyIsEmpty()
    {
        $this->assertEmpty(
            $this->product->getSpecialPrices()
        );
    }

    /**
     * @test
     */
    public function setSpecialPricesSetsSpecialPrices()
    {
        $price = 10.00;

        $specialPrice = new SpecialPrice();
        $specialPrice->setPrice($price);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($specialPrice);

        $this->product->setSpecialPrices($objectStorage);

        $this->assertContains(
            $specialPrice,
            $this->product->getSpecialPrices()
        );
    }

    /**
     * @test
     */
    public function addSpecialPriceAddsSpecialPrice()
    {
        $price = 10.00;

        $specialPrice = new SpecialPrice();
        $specialPrice->setPrice($price);

        $this->product->addSpecialPrice($specialPrice);

        $this->assertContains(
            $specialPrice,
            $this->product->getSpecialPrices()
        );
    }

    /**
     * @test
     */
    public function removeSpecialPriceRemovesSpecialPrice()
    {
        $price = 10.00;

        $specialPrice = new SpecialPrice();
        $specialPrice->setPrice($price);

        $this->product->addSpecialPrice($specialPrice);
        $this->product->removeSpecialPrice($specialPrice);

        $this->assertEmpty(
            $this->product->getSpecialPrices()
        );
    }

    /**
     * @test
     */
    public function getBestSpecialPriceDiscountForEmptySpecialPriceReturnsDiscount()
    {
        $price = 10.00;

        $product = new Product();
        $product->setPrice($price);

        $this->assertSame(
            0.0,
            $product->getBestSpecialPriceDiscount()
        );
    }

    /**
     * @test
     * @dataProvider bestSpecialPriceProvider
     */
    public function getBestSpecialPriceForGivenSpecialPricesReturnsBestSpecialPrice(
        $price,
        $special1,
        $special2,
        $special3,
        $expectedBestSpecialPrice
    ) {
        $product = new Product();
        $product->setPrice($price);

        $specialPrice1 = new SpecialPrice();
        $specialPrice1->setPrice($special1);
        $product->addSpecialPrice($specialPrice1);

        $specialPrice2 = new SpecialPrice();
        $specialPrice2->setPrice($special2);
        $product->addSpecialPrice($specialPrice2);

        $specialPrice3 = new SpecialPrice();
        $specialPrice3->setPrice($special3);
        $product->addSpecialPrice($specialPrice3);

        $this->assertSame(
            $expectedBestSpecialPrice,
            $product->getBestSpecialPrice()
        );
    }

    /**
     * @test
     */
    public function getBestSpecialPriceDiscountForGivenSpecialPriceReturnsPercentageDiscount()
    {
        $price = 10.0;
        $porductSpecialPrice = 9.0;

        $product = new Product();
        $product->setPrice($price);

        $specialPrice = new SpecialPrice();
        $specialPrice->setPrice($porductSpecialPrice);

        $product->addSpecialPrice($specialPrice);

        $this->assertSame(
            10.0,
            $product->getBestSpecialPricePercentageDiscount()
        );
    }

    /**
     * @test
     * @dataProvider bestSpecialPriceDiscountProvider
     */
    public function getBestSpecialPriceDiscountForGivenSpecialPricesReturnsBestPercentageDiscount(
        $price,
        $special1,
        $special2,
        $special3,
        $expectedBestSpecialPriceDiscount
    ) {
        $product = new Product();
        $product->setPrice($price);

        $specialPrice1 = new SpecialPrice();
        $specialPrice1->setPrice($special1);
        $product->addSpecialPrice($specialPrice1);

        $specialPrice2 = new SpecialPrice();
        $specialPrice2->setPrice($special2);
        $product->addSpecialPrice($specialPrice2);

        $specialPrice3 = new SpecialPrice();
        $specialPrice3->setPrice($special3);
        $product->addSpecialPrice($specialPrice3);

        $this->assertSame(
            $expectedBestSpecialPriceDiscount,
            $product->getBestSpecialPriceDiscount()
        );
    }

    /**
     * @test
     */
    public function getStockWithoutHandleStockInitiallyReturnsIntMax()
    {
        $product = new Product();

        $this->assertSame(
            PHP_INT_MAX,
            $product->getStock()
        );
    }

    /**
     * @test
     */
    public function getStockWithHandleStockInitiallyReturnsZero()
    {
        $product = new Product();
        $product->setHandleStock(true);

        $this->assertSame(
            0,
            $product->getStock()
        );
    }

    /**
     * @test
     */
    public function setStockWithHandleStockSetsStock()
    {
        $stock = 10;

        $product = new Product();
        $product->setStock($stock);
        $product->setHandleStock(true);

        $this->assertSame(
            $stock,
            $product->getStock()
        );

        $product->setHandleStock(false);

        $this->assertSame(
            PHP_INT_MAX,
            $product->getStock()
        );
    }

    /**
     * @test
     */
    public function addToStockAddsANumberOfProductsToStock()
    {
        $numberOfProducts = 10;

        $product = new Product();
        $product->setHandleStock(true);
        $product->addToStock($numberOfProducts);

        $this->assertSame(
            $numberOfProducts,
            $product->getStock()
        );
    }

    /**
     * @test
     */
    public function removeFromStockAddsRemovesANumberOfProductsFromStock()
    {
        $stock = 100;
        $numberOfProducts = 10;

        $product = new Product();
        $product->setHandleStock(true);
        $product->setStock($stock);
        $product->removeFromStock($numberOfProducts);

        $this->assertSame(
            ($stock - $numberOfProducts),
            $product->getStock()
        );
    }

    /**
     * @test
     */
    public function handleStockInitiallyReturnsFalse()
    {
        $product = new Product();

        $this->assertFalse(
            $product->isHandleStock()
        );
    }

    /**
     * @test
     */
    public function setHandleStockSetsHandleStock()
    {
        $product = new Product();
        $product->setHandleStock(true);

        $this->assertTrue(
            $product->isHandleStock()
        );
    }

    /**
     * @test
     */
    public function isAvailableInitiallyReturnsTrue()
    {
        $product = new Product();

        $this->assertTrue(
            $product->getIsAvailable()
        );
    }

    /**
     * @test
     */
    public function isAvailableWithHandleStockIsEnabledAndEmptyStockReturnsFalse()
    {
        $product = new Product();
        $product->setHandleStock(true);

        $this->assertFalse(
            $product->getIsAvailable()
        );
    }

    /**
     * @test
     */
    public function isAvailableWithHandleStockIsEnabledAndNotEmptyStockReturnsTrue()
    {
        $product = new Product();
        $product->setStock(10);
        $product->setHandleStock(true);

        $this->assertTrue(
            $product->getIsAvailable()
        );
    }

    /**
     * @test
     */
    public function isAvailableWithHandleStockAndHandleStockInVariantsIsEnabledAndNoBackendVariantsConfiguredReturnsFalse()
    {
        $product = new Product();
        $product->setStock(10);
        $product->setHandleStock(true);
        $product->setHandleStockInVariants(true);

        $this->assertFalse(
            $product->getIsAvailable()
        );
    }

    /**
     * @test
     */
    public function isAvailableWithHandleStockAndHandleStockInVariantsIsEnabledAndBackendVariantConfiguredIsNotAvailableReturnsFalse()
    {
        $productBackendVariant = $this->createMock(
            BeVariant::class
        );
        $productBackendVariant->expects($this->any())->method('getIsAvailable')->will($this->returnValue(false));

        $product = new Product();
        $product->addBeVariant($productBackendVariant);
        $product->setStock(10);
        $product->setHandleStock(true);
        $product->setHandleStockInVariants(true);

        $this->assertFalse(
            $product->getIsAvailable()
        );
    }

    /**
     * @test
     */
    public function isAvailableWithHandleStockAndHandleStockInVariantsIsEnabledAndBackendVariantConfiguredIsAvailableReturnsFalse()
    {
        $productBackendVariant = $this->createMock(
            BeVariant::class
        );
        $productBackendVariant->expects($this->any())->method('getIsAvailable')->will($this->returnValue(true));

        $product = new Product();
        $product->addBeVariant($productBackendVariant);
        $product->setStock(10);
        $product->setHandleStock(true);
        $product->setHandleStockInVariants(true);

        $this->assertTrue(
            $product->getIsAvailable()
        );
    }

    /**
     * @test
     */
    public function getPriceMeasure()
    {
        $this->assertSame(
            0.0,
            $this->product->getPriceMeasure()
        );
    }

    /**
     * @test
     */
    public function setPriceMeasureSetsPriceMeasure()
    {
        $priceMeasure = 10.99;

        $this->product->setPriceMeasure($priceMeasure);

        $this->assertSame(
            $priceMeasure,
            $this->product->getPriceMeasure()
        );
    }

    /**
     * @test
     */
    public function getPriceMeasureUnit()
    {
        $this->assertSame(
            '',
            $this->product->getPriceMeasureUnit()
        );
    }

    /**
     * @test
     */
    public function setPriceMeasureUnitSetsPriceMeasureUnit()
    {
        $priceMeasureUnit = 'l';

        $this->product->setPriceMeasureUnit($priceMeasureUnit);

        $this->assertSame(
            $priceMeasureUnit,
            $this->product->getPriceMeasureUnit()
        );
    }

    /**
     * @test
     */
    public function getBasePriceMeasureUnit()
    {
        $this->assertSame(
            '',
            $this->product->getBasePriceMeasureUnit()
        );
    }

    /**
     * @test
     */
    public function setBasePriceMeasureUnitSetsBasePriceMeasureUnit()
    {
        $priceBaseMeasureUnit = 'l';

        $this->product->setBasePriceMeasureUnit($priceBaseMeasureUnit);

        $this->assertSame(
            $priceBaseMeasureUnit,
            $this->product->getBasePriceMeasureUnit()
        );
    }

    /**
     * @test
     */
    public function getIsMeasureUnitCompatibilityInitiallyRetrunsFalse()
    {
        $product = new Product();

        $this->assertFalse(
            $product->getIsMeasureUnitCompatibility()
        );
    }

    /**
     * @test
     */
    public function getIsMeasureUnitCompatibilityAndNotSetPriceMeasureUnitsRetrunsFalse()
    {
        $product = new Product();
        $product->setBasePriceMeasureUnit('l');

        $this->assertFalse(
            $product->getIsMeasureUnitCompatibility()
        );
    }

    /**
     * @test
     */
    public function getIsMeasureUnitCompatibilityAndNotSetBasePriceMeasureUnitsRetrunsFalse()
    {
        $product = new Product();
        $product->setPriceMeasureUnit('l');

        $this->assertFalse(
            $product->getIsMeasureUnitCompatibility()
        );
    }

    /**
     * Measurement Units Provider
     *
     * @return array
     */
    public function measureUnitsProvider()
    {
        return [
            ['mg',  'kg', 1000000.0, 1000.0, 1000.0],
            ['g',   'kg', 1000.0,    1000.0, 1.0],
            ['kg',  'kg', 1.0,       1000.0, 0.001],
            ['ml',  'l',  1000.0,    1000.0, 1.0],
            ['cl',  'l',  100.0,     1000.0, 0.1],
            ['l',   'l',  1.0,       1000.0, 0.001],
            ['cbm', 'l',  0.001,     1.0,    0.001],
            ['mm',  'm',  1000.0,    1000.0, 1.0],
            ['cm',  'm',  100.0,     1000.0, 0.1],
            ['m',   'm',  1.0,       2.0,    0.5],
            ['km',  'm',  0.001,     2.0,    0.0005],
            ['m2',  'm2', 1.0,       20.0,   0.05],
        ];
    }

    /**
     * @test
     * @dataProvider measureUnitsProvider
     */
    public function getIsMeasureUnitCompatibilityRetrunsTrueOnSameTypeOfMeasureUnit(
        $sourceMeasureUnit,
        $targetMeasureUnit,
        $factor,
        $priceMeasure,
        $calculatedBasePrice
    ) {
        $product = new Product();
        $product->setPriceMeasureUnit($sourceMeasureUnit);
        $product->setBasePriceMeasureUnit($targetMeasureUnit);

        $this->assertTrue(
            $product->getIsMeasureUnitCompatibility()
        );
    }

    /**
     * @test
     * @dataProvider measureUnitsProvider
     */
    public function getMeasureUnitFactorForGivenPriceMeasureUnitAndBasePriceMeasureUnitRetrunsFactor(
        $sourceMeasureUnit,
        $targetMeasureUnit,
        $factor,
        $priceMeasure,
        $calculatedBasePrice
    ) {
        $product = new Product();
        $product->setPriceMeasureUnit($sourceMeasureUnit);
        $product->setBasePriceMeasureUnit($targetMeasureUnit);
        $product->setPriceMeasure(1);

        $this->assertSame(
            $factor,
            $product->getMeasureUnitFactor()
        );
    }

    /**
     * @test
     * @dataProvider measureUnitsProvider
     */
    public function getCalculatedBasePriceForGivenPriceMeasureUnitAndBasePriceMeasureUnitRetrunsPrice(
        $sourceMeasureUnit,
        $targetMeasureUnit,
        $factor,
        $priceMeasure,
        $calculatedBasePrice
    ) {
        $product = new Product();
        $product->setPriceMeasureUnit($sourceMeasureUnit);
        $product->setBasePriceMeasureUnit($targetMeasureUnit);
        $product->setPriceMeasure($priceMeasure);

        $this->assertSame(
            $calculatedBasePrice,
            $product->getMeasureUnitFactor()
        );
    }

    /**
     * @test
     */
    public function getServiceAttribute1ReturnsZero()
    {
        $this->assertSame(
            0.0,
            $this->product->getServiceAttribute1()
        );
    }

    /**
     * @test
     */
    public function setServiceAttribute1SetsServiceAttribute1()
    {
        $serviceAttribute1 = 1.0;

        $this->product->setServiceAttribute1($serviceAttribute1);

        $this->assertSame(
            $serviceAttribute1,
            $this->product->getServiceAttribute1()
        );
    }

    /**
     * @test
     */
    public function getServiceAttribute2ReturnsZero()
    {
        $this->assertSame(
            0.0,
            $this->product->getServiceAttribute2()
        );
    }

    /**
     * @test
     */
    public function setServiceAttribute2SetsServiceAttribute2()
    {
        $serviceAttribute2 = 2.0;

        $this->product->setServiceAttribute2($serviceAttribute2);

        $this->assertSame(
            $serviceAttribute2,
            $this->product->getServiceAttribute2()
        );
    }

    /**
     * @test
     */
    public function getServiceAttribute3ReturnsZero()
    {
        $this->assertSame(
            0.0,
            $this->product->getServiceAttribute3()
        );
    }

    /**
     * @test
     */
    public function setServiceAttribute3SetsServiceAttribute3()
    {
        $serviceAttribute3 = 3.0;

        $this->product->setServiceAttribute3($serviceAttribute3);

        $this->assertSame(
            $serviceAttribute3,
            $this->product->getServiceAttribute3()
        );
    }

    /**
     * @test
     */
    public function getTaxClassIdInitiallyReturnsTaxClassId()
    {
        $this->assertSame(
            1,
            $this->product->getTaxClassId()
        );
    }

    /**
     * @test
     */
    public function setTaxClassIdSetsTaxClassId()
    {
        $taxClassId = 2;

        $this->product->setTaxClassId($taxClassId);

        $this->assertSame(
            $taxClassId,
            $this->product->getTaxClassId()
        );
    }

    /**
     * @test
     */
    public function getBeVariantAttribute1InitiallyIsNull()
    {
        $this->assertNull(
            $this->product->getBeVariantAttribute1()
        );
    }

    /**
     * @test
     */
    public function setBeVariantAttribute1SetsBeVariantAttribute1()
    {
        $beVariantAttribute = new BeVariantAttribute();

        $this->product->setBeVariantAttribute1($beVariantAttribute);

        $this->assertSame(
            $beVariantAttribute,
            $this->product->getBeVariantAttribute1()
        );
    }

    /**
     * @test
     */
    public function getBeVariantAttribute2InitiallyIsNull()
    {
        $this->assertNull(
            $this->product->getBeVariantAttribute2()
        );
    }

    /**
     * @test
     */
    public function setBeVariantAttribute2SetsBeVariantAttribute2()
    {
        $beVariantAttribute = new BeVariantAttribute();

        $this->product->setBeVariantAttribute2($beVariantAttribute);

        $this->assertSame(
            $beVariantAttribute,
            $this->product->getBeVariantAttribute2()
        );
    }

    /**
     * @test
     */
    public function getBeVariantAttribute3InitiallyIsNull()
    {
        $this->assertNull(
            $this->product->getBeVariantAttribute3()
        );
    }

    /**
     * @test
     */
    public function setBeVariantAttribute3SetsBeVariantAttribute3()
    {
        $beVariantAttribute = new BeVariantAttribute();

        $this->product->setBeVariantAttribute3($beVariantAttribute);

        $this->assertSame(
            $beVariantAttribute,
            $this->product->getBeVariantAttribute3()
        );
    }

    /**
     * @test
     */
    public function getVariantsInitiallyIsEmpty()
    {
        $this->assertEmpty(
            $this->product->getBeVariants()
        );
    }

    /**
     * @test
     */
    public function setVariantsSetsVariants()
    {
        $variant = new BeVariant();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($variant);

        $this->product->setBeVariants($objectStorage);

        $this->assertContains(
            $variant,
            $this->product->getBeVariants()
        );
    }

    /**
     * @test
     */
    public function addVariantAddsVariant()
    {
        $variant = new BeVariant();

        $this->product->addBeVariant($variant);

        $this->assertContains(
            $variant,
            $this->product->getBeVariants()
        );
    }

    /**
     * @test
     */
    public function removeVariantRemovesVariant()
    {
        $variant = new BeVariant();

        $this->product->addBeVariant($variant);
        $this->product->removeBeVariant($variant);

        $this->assertEmpty(
            $this->product->getBeVariants()
        );
    }
}
