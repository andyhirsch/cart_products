<?php

namespace Extcode\CartProducts\Controller;

/*
 * This file is part of the package extcode/cart-products.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Extcode\Cart\Service\SessionHandler;
use Extcode\Cart\Utility\CartUtility;
use Extcode\CartProducts\Domain\Model\Dto\Product\ProductDemand;
use Extcode\CartProducts\Domain\Model\Product\Product;
use Extcode\CartProducts\Domain\Repository\CategoryRepository;
use Extcode\CartProducts\Domain\Repository\Product\ProductRepository;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;

class ProductController extends ActionController
{
    /**
     * Session Handler
     *
     * @var \Extcode\Cart\Service\SessionHandler
     */
    protected $sessionHandler;

    /**
     * Cart Utility
     *
     * @var \Extcode\Cart\Utility\CartUtility
     */
    protected $cartUtility;

    /**
     * productRepository
     *
     * @var \Extcode\CartProducts\Domain\Repository\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * categoryRepository
     *
     * @var \Extcode\CartProducts\Domain\Repository\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * Search Arguments
     *
     * @var array
     */
    protected $searchArguments;

    /**
     * @var array
     */
    protected $cartSettings = [];

    /**
     * @param \Extcode\Cart\Service\SessionHandler $sessionHandler
     */
    public function injectSessionHandler(
        SessionHandler $sessionHandler
    ) {
        $this->sessionHandler = $sessionHandler;
    }

    /**
     * @param \Extcode\Cart\Utility\CartUtility $cartUtility
     */
    public function injectCartUtility(
        CartUtility $cartUtility
    ) {
        $this->cartUtility = $cartUtility;
    }

    /**
     * @param \Extcode\CartProducts\Domain\Repository\Product\ProductRepository $productRepository
     */
    public function injectProductRepository(
        ProductRepository $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * @param \Extcode\CartProducts\Domain\Repository\CategoryRepository $categoryRepository
     */
    public function injectCategoryRepository(
        CategoryRepository $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Action initializer
     */
    protected function initializeAction()
    {
        $this->cartSettings = $this->configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'Cart'
        );

        if (!empty($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE'])) {
            static $cacheTagsSet = false;

            /** @var $typoScriptFrontendController \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
            $typoScriptFrontendController = $GLOBALS['TSFE'];
            if (!$cacheTagsSet) {
                $typoScriptFrontendController->addCacheTags(['tx_cartproducts']);
                $cacheTagsSet = true;
            }
        }
    }

    /**
     * Create the demand object which define which records will get shown
     *
     * @param array $settings
     *
     * @return \Extcode\CartProducts\Domain\Model\Dto\Product\ProductDemand
     */
    protected function createDemandObjectFromSettings($settings)
    {
        /** @var \Extcode\CartProducts\Domain\Model\Dto\Product\ProductDemand $demand */
        $demand = GeneralUtility::makeInstance(
            ProductDemand::class
        );

        if ($this->searchArguments['sku']) {
            $demand->setSku($this->searchArguments['sku']);
        }
        if ($this->searchArguments['title']) {
            $demand->setTitle($this->searchArguments['title']);
        }
        if ($settings['orderBy']) {
            $demand->setOrder($settings['orderBy'] . ' ' . $settings['orderDirection']);
        }

        $this->addCategoriesToDemandObjectFromSettings($demand);

        return $demand;
    }

    /**
     * @param \Extcode\CartProducts\Domain\Model\Dto\Product\ProductDemand $demand
     */
    protected function addCategoriesToDemandObjectFromSettings(&$demand)
    {
        if ($this->settings['categoriesList']) {
            $selectedCategories = GeneralUtility::intExplode(
                ',',
                $this->settings['categoriesList'],
                true
            );

            $categories = [];

            if ($this->settings['listSubcategories']) {
                foreach ($selectedCategories as $selectedCategory) {
                    $category = $this->categoryRepository->findByUid($selectedCategory);
                    $categories = array_merge(
                        $categories,
                        $this->categoryRepository->findSubcategoriesRecursiveAsArray($category)
                    );
                }
            } else {
                $categories = $selectedCategories;
            }

            $demand->setCategories($categories);
        }
    }

    /**
     * action list
     */
    public function listAction()
    {
        $demand = $this->createDemandObjectFromSettings($this->settings);
        $demand->setActionAndClass(__METHOD__, __CLASS__);

        $products = $this->productRepository->findDemanded($demand);

        $this->view->assign('searchArguments', $this->searchArguments);
        $this->view->assign('products', $products);
        $this->view->assign('cartSettings', $this->cartSettings);

        $this->assignCurrencyTranslationData();

        $this->addCacheTags($products);
    }

    /**
     * action show
     *
     * @param \Extcode\CartProducts\Domain\Model\Product\Product $product
     *
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("product")
     */
    public function showAction(Product $product = null)
    {
        if (!$product) {
            $product = $this->getProduct();
        }
        if (!$product) {
            $this->forward('list');
        }

        $this->view->assign('user', $GLOBALS['TSFE']->fe_user->user);
        $this->view->assign('product', $product);
        $this->view->assign('cartSettings', $this->cartSettings);

        $this->assignCurrencyTranslationData();

        $this->addCacheTags([$product]);
    }

    /**
     * action showForm
     *
     * @param \Extcode\CartProducts\Domain\Model\Product\Product $product
     */
    public function showFormAction(Product $product = null)
    {
        if (!$product) {
            $product = $this->getProduct();
        }

        $this->view->assign('product', $product);
        $this->view->assign('cartSettings', $this->cartSettings);

        $this->assignCurrencyTranslationData();
    }

    /**
     * action teaser
     */
    public function teaserAction()
    {
        $products = $this->productRepository->findByUids($this->settings['productUids']);

        $this->view->assign('products', $products);
        $this->view->assign('cartSettings', $this->cartSettings);

        $this->assignCurrencyTranslationData();

        $this->addCacheTags($products);
    }

    /**
     * action flexform
     */
    public function flexformAction()
    {
        $this->contentObj = $this->configurationManager->getContentObject();
        $contentId = $this->contentObj->data['uid'];

        $this->view->assign('contentId', $contentId);
    }

    /**
     * @return \Extcode\CartProducts\Domain\Model\Product\Product
     */
    protected function getProduct()
    {
        $productUid = 0;

        if ((int)$GLOBALS['TSFE']->page['doktype'] === 183) {
            $productUid = (int)$GLOBALS['TSFE']->page['cart_products_product'];
        } else {
            if ($this->request->getPluginName() === 'ProductPartial') {
                if ($productUid === 0) {
                    $configurationManager = GeneralUtility::makeInstance(
                        ConfigurationManager::class
                    );
                    $configuration = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK);

                    $typoscriptService = GeneralUtility::makeInstance(
                        TypoScriptService::class
                    );
                    $configuration = $typoscriptService->convertPlainArrayToTypoScriptArray($configuration);
                    $productUid = (int)$configurationManager->getContentObject()->cObjGetSingle($configuration['product'], $configuration['product.']);
                }
                if ($productUid === 0) {
                    $requestBuilder = GeneralUtility::makeInstance(
                        RequestBuilder::class
                    );
                    $configurationManager = GeneralUtility::makeInstance(
                        ConfigurationManager::class
                    );
                    $configurationManager->setConfiguration([
                        'vendorName' => 'Extcode',
                        'extensionName' => 'CartProducts',
                        'pluginName' => 'Products',
                    ]);
                    $requestBuilder->injectConfigurationManager($configurationManager);

                    /**
                     * @var \TYPO3\CMS\Extbase\Mvc\Request $cartProductRequest
                     */
                    $cartProductRequest = $requestBuilder->build();

                    if ($cartProductRequest->hasArgument('product')) {
                        $productUid = (int)$cartProductRequest->getArgument('product');
                    }
                }
            }
        }

        if ($productUid > 0) {
            $productRepository = GeneralUtility::makeInstance(
                ProductRepository::class
            );

            $product =  $productRepository->findByUid($productUid);
        }

        return $product;
    }

    /**
     * assigns currency translation array to view
     */
    protected function assignCurrencyTranslationData()
    {
        if (TYPO3_MODE === 'FE') {
            $currencyTranslationData = [];

            $cart = $this->sessionHandler->restore($this->settings['cart']['pid']);

            if ($cart) {
                $currencyTranslationData['currencyCode'] = $cart->getCurrencyCode();
                $currencyTranslationData['currencySign'] = $cart->getCurrencySign();
                $currencyTranslationData['currencyTranslation'] = $cart->getCurrencyTranslation();
            }

            $this->view->assign('currencyTranslationData', $currencyTranslationData);
        }
    }

    /**
     * @param $products
     */
    protected function addCacheTags($products)
    {
        $cacheTags = [];

        foreach ($products as $product) {
            // cache tag for each product record
            $cacheTags[] = 'tx_cartproducts_product_' . $product->getUid();
        }
        if (count($cacheTags) > 0) {
            $GLOBALS['TSFE']->addCacheTags($cacheTags);
        }
    }
}
