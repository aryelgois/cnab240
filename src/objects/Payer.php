<?php
/**
 * This Software is part of aryelgois\cnab240 and is provided "as is".
 *
 * @see LICENSE
 */

namespace aryelgois\cnab240\objects;

use aryelgois\utils\Database;
use aryelgois\objects;
use aryelgois\cnab240\Cnab240File;

/**
 * A Payer object loaded from a database
 *
 * @author Aryel Mota Góis
 * @license MIT
 * @link https://www.github.com/aryelgois/cnab240
 * @version 0.2
 */
class Payer extends objects\Person
{
    /**
     * [desc]
     *
     * @var float
     */
    //public $balance;
    
    /**
     * Contains a string ready to be inserted into the Shipping File
     *
     * @var string
     */
    public $cnab240_string;
    
    /**
     * Creates a new Payer object
     *
     * @param Database $db_cnab240 ..
     * @param Database $db_address ..
     * @param integer  $id         ..
     *
     * @throws RuntimeException If it can not load from database
     */
    public function __construct(Database $db_cnab240, Database $db_address, $id)
    {
        $payer = Database::fetch($db_cnab240->query("SELECT * FROM `payers` WHERE `id` = " . $id));
        if (empty($payer)) {
            throw new \RuntimeException('Could not load payer from database');
        }
        $payer = $payer[0];
        
        $address_data = Database::fetch($db_cnab240->query("SELECT * FROM `fulladdress` WHERE `id` = " . $payer['address']));
        if (empty($address_data)) {
            throw new \RuntimeException('Could not load payer\'s address from database');
        }
        $address_data = $address_data[0];
        
        $address = new objects\FullAddress(
            $db_address,
            $address_data['county'],
            $address_data['neighborhood'],
            $address_data['place'],
            $address_data['number'],
            $address_data['zipcode'],
            $address_data['detail']
        );
        
        parent::__construct($payer['name'], $payer['document']);
        $this->address = [$address];
        //$this->balance = $payer['balance'];
        
        $this->formatCnab240();
    }
    
    /**
     * Formats $this data to be CNAB240 compliant
     */
    protected function formatCnab240()
    {
        $a = $this->address[0];
        $result = Cnab240File::formatDocument($this, 15)
                . Cnab240File::padAlfa($this->name, 40)
                . Cnab240File::padAlfa($a->place . ', ' . $a->number . ($a->detail != '' ? ', ' . $a->detail : ''), 40)
                . Cnab240File::padAlfa($a->neighborhood, 15)
                . $a->zipcode // 8 digits
                . Cnab240File::padAlfa($a->county['name'], 15)
                . $a->state['code'];
        
        $this->cnab240_string = $result;
    }
}