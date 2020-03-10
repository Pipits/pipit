<?php

    include(__DIR__.'/runtime/util.php');
    include(__DIR__.'/runtime/categories.php');


    /**
     * Get the key of the collection an item belongs to
     * 
     * @param int $itemID
     */
    function pipit_get_collection_key_for($itemID) {
        $API = new PerchAPI(1.0, 'pipit');
        $DB = $API->get('DB');

        $items_table = PERCH_DB_PREFIX . 'collection_items';
        $collections_table = PERCH_DB_PREFIX . 'collections';
        $itemID = $DB->pdb($itemID);
        
        return $DB->get_value("SELECT collectionKey FROM $collections_table  WHERE collectionID IN (SELECT collectionID from $items_table WHERE itemID=$itemID) LIMIT 1");
    }