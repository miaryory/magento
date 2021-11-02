<?php

namespace Logisnap\SaveOrder\Model\LogisnapOrder;

class LogisnapInBulkOrderActorDTO extends \Magento\Framework\Model\AbstractModel
{

    public $StatusID;
    public $Name;
    public $ContactType;
    public $TypeID;
    public $Adr1;
    public $PostalCode;
    public $City;
    public $Phone;
    public $Email;

    public function __construct($data = []){
        $this->StatusID = $data['StatusID'];
        $this->Name = $data['Name'];
        $this->ContactType = $data['ContactType'];
        $this->TypeID = $data['TypeID'];
        $this->Adr1 = $data['Adr1'];
        $this->PostalCode = $data['PostalCode'];
        $this->City = $data['City'];
        $this->Phone = $data['Phone'];
        $this->Email = $data['Email'];
    }

}