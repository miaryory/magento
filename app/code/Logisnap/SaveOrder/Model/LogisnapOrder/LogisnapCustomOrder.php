<?php

namespace Logisnap\SaveOrder\Model\LogisnapOrder;

use Logisnap\SaveOrder\Model\LogisnapOrder\LogisnapContactInformationFactory;

class LogisnapCustomOrder extends \Magento\Framework\Model\AbstractModel
{

    public $ClientShipmentTypeUID;
    public $PickDate;
    public $DeliveryDate;
    public $Number;
    public $Ref1;
    public $TypeID;
    public $StatusID;
    public $ContactInformation; //LogisnapContactInformation

    public function __construct($data = [], LogisnapContactInformationFactory $logisnapContactInformationFactory){
        $this->ClientShipmentTypeUID = $data['ClientShipmentTypeUID'];
        $this->PickDate = $data['PickDate'];
        $this->DeliveryDate = $data['DeliveryDate'];
        $this->Number = $data['Number'];
        $this->Ref1 = $data['Ref1'];
        $this->TypeID = $data['TypeID'];
        $this->StatusID = $data['StatusID'];
        $this->ContactInformation = $logisnapContactInformationFactory->create(['data' => $data['ContactInformation']]);
    }

}