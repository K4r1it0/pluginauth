<?php
namespace db;

/**
 *
 * @author dwilbanks
 * @property int $id
 * @property \DateTime $timestamp
 * @property string $ipaddress
 */
class loginhistoryRecord extends \dw\_dbrecord {
    static function create() {
        $historyproxy = new \db\loginhistoryRecord();
        $historyproxy->ipaddress = $_SERVER['REMOTE_ADDR'];
        $historyproxy->save();
    }
}