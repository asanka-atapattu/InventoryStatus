<?php
class Oc_InventoryStatus_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     * This method will handle inventory status update when the credit memo placed
     */
    public function oc_creditmemo_inventory_status(Varien_Event_Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();

        foreach ($creditmemo->getAllItems() as $item) {
            $productId = $item->getProductId();
            $product = Mage::getModel('catalog/product')->load($productId);

            /*Get simple products*/
            if (!$product->isConfigurable()) {
                $return = false;
                if ($item->hasBackToStock()) {
                    if ($item->getBackToStock() && $item->getQty()) {
                        $return = true;
                    }
                } elseif (Mage::helper('cataloginventory')->isAutoReturnEnabled()) {
                    $return = true;
                }
                if ($return) {
                    $parentOrderId = $item->getOrderItem()->getParentItemId();
                    $parentItem = $parentOrderId ? $creditmemo->getItemByOrderId($parentOrderId) : false;
                    $qty = $parentItem ? ($parentItem->getQty() * $item->getQty()) : $item->getQty();
                    /*Update status only if qty > 0*/
                    if ($qty >= 1) {
                        $stockItem = $product->getStockItem();
                        if ($stockItem->getIsInStock()) {
                            // in stock!;
                        } else {
                            $stockItem->setIsInStock(1);
                            $stockItem->save();
                            /*Save product inventory status*/
                            $product->setStockItem($stockItem);
                            $product->save();
                        }
                    }
                }
            }
        }
    }
}
