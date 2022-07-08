<?php

namespace Extcode\CartProducts\Hooks;

/*
 * This file is part of the package extcode/cart-products.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Tpwd\KeSearch\Indexer\IndexerRunner;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class KeSearchIndexer
{
    /**
     * Registers the indexer configuration
     *
     * @param array $params
     * @param $pObj
     */
    public function registerIndexerConfiguration(array &$params, $pObj): void
    {
        $newArray = [
            'Cart Product Indexer',
            'cartproductindexer',
            ExtensionManagementUtility::extPath('cart_products') . 'Resources/Public/Icons/Extension.svg'
        ];
        $params['items'][] = $newArray;
    }

    public function customIndexer(array &$indexerConfig, IndexerRunner &$indexerObject): string
    {
        if ($indexerConfig['type'] === 'cartproductindexer') {
            return $this->cartProductIndexer($indexerConfig, $indexerObject);
        }

        return '';
    }

    public function cartProductIndexer(array &$indexerConfig, IndexerRunner &$indexerObject): string
    {
        $productIndexerName = 'Product Indexer "' . $indexerConfig['title'] . '"';

        $indexPids = $this->getPidList($indexerConfig);

        if ($indexPids === '') {
            $productIndexerMessage = 'ERROR: No Storage Pids configured!';
        } else {
            $products = $this->getProductsToIndex($indexPids);

            if ($products) {
                foreach ($products as $product) {
                    // compile the information which should go into the index
                    // the field names depend on the table you want to index!
                    $sku = strip_tags($product['sku']);
                    $title = strip_tags($product['title']);
                    $teaser = strip_tags($product['teaser']);
                    $description = strip_tags($product['description']);

                    $fullContent = $sku . "\n" . $title . "\n" . $teaser . "\n" . $description;
                    $params = '&tx_cartproducts_products[product]=' . $product['uid'];
                    $tags = '#product#';
                    $additionalFields = [
                        'sortdate' => $product['crdate'],
                        'orig_uid' => $product['uid'],
                        'orig_pid' => $product['pid'],
                    ];

                    $targetPid = $this->getTargetPidFormCategory($product['category']);

                    if ($targetPid === 0) {
                        $targetPid = $indexerConfig['targetpid'];
                    }

                    $indexerObject->storeInIndex(
                        $indexerConfig['storagepid'], // storage PID
                        $title, // record title
                        'cartproduct', // content type
                        $targetPid, // target PID: where is the single view?
                        $fullContent, // indexed content, includes the title (linebreak after title)
                        $tags, // tags for faceted search
                        $params, // typolink params for singleview
                        $teaser, // abstract; shown in result list if not empty
                        $product['sys_language_uid'], // language uid
                        $product['starttime'], // starttime
                        $product['endtime'], // endtime
                        $product['fe_group'], // fe_group
                        false, // debug only?
                        $additionalFields // additionalFields
                    );
                }
                $productIndexerMessage = 'Success: ' . count($products) . ' products has been indexed.';
            } else {
                $productIndexerMessage = 'Warning: No product found in configured Storage Pids.';
            }
        }

        return '<p><b>' . $productIndexerName . '</b><br/><strong>' . $productIndexerMessage . '</strong></p>';
    }

    protected function getPidList(array $config): string
    {
        $recursivePids = $this->extendPidListByChildren($config['startingpoints_recursive'], 99);
        if ($config['sysfolder']) {
            return $recursivePids . ',' . $config['sysfolder'];
        } else {
            return $recursivePids;
        }
    }

    protected function extendPidListByChildren(string $pidList = '', int $recursive = 0): string
    {
        $recursive = (int)$recursive;

        if ($recursive <= 0) {
            return $pidList;
        }

        $queryGenerator = GeneralUtility::makeInstance(
            QueryGenerator::class
        );
        $recursiveStoragePids = $pidList;
        $storagePids = GeneralUtility::intExplode(',', $pidList);
        foreach ($storagePids as $startPid) {
            $pids = $queryGenerator->getTreeList($startPid, $recursive, 0, 1);
            if (strlen($pids) > 0) {
                $recursiveStoragePids .= ',' . $pids;
            }
        }
        return $recursiveStoragePids;
    }

    /**
     * Returns all products for a given PidList
     */
    protected function getProductsToIndex(string $indexPids): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_cartproducts_domain_model_product_product');

        $queryBuilder
            ->select('*')
            ->from('tx_cartproducts_domain_model_product_product')
            ->where(
                $queryBuilder->expr()->in('tx_cartproducts_domain_model_product_product.pid', $indexPids)
            );

        $products = $queryBuilder->execute()->fetchAll();

        return $products;
    }

    protected function getTargetPidFormCategory($categoryUid): ?int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_category');

        $constraints = [
            $queryBuilder->expr()->eq('sys_category_mm.tablenames', $queryBuilder->createNamedParameter('tx_cartproducts_domain_model_product_product', \PDO::PARAM_STR)),
            $queryBuilder->expr()->eq('sys_category_mm.fieldname', $queryBuilder->createNamedParameter('category', \PDO::PARAM_STR)),
            $queryBuilder->expr()->eq('sys_category_mm.uid_foreign', $queryBuilder->createNamedParameter($categoryUid, \PDO::PARAM_INT)),
        ];

        $queryBuilder
            ->select('sys_category.cart_product_show_pid')
            ->from('sys_category')
            ->leftJoin(
                'sys_category',
                'sys_category_record_mm',
                'sys_category_mm',
                $queryBuilder->expr()->eq(
                    'sys_category_mm.uid_local',
                    $queryBuilder->quoteIdentifier('sys_category.uid')
                )
            )
            ->where(...$constraints);

        $sys_category = $queryBuilder->execute()->fetch();

        return $sys_category['cart_product_show_pid'];
    }
}
