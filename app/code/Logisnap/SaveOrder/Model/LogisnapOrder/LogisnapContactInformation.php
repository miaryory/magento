<?php

namespace Logisnap\SaveOrder\Model\LogisnapOrder;

use Logisnap\SaveOrder\Model\LogisnapOrder\LogisnapInBulkOrderActorDTOFactory;

class LogisnapContactInformation extends \Magento\Framework\Model\AbstractModel
{

    public $Receiver; // LogisnapInBulkOrderActorDTO
    public $Sender; //LogisnapInBulkOrderActorDTO

    public function __construct($data = [], LogisnapInBulkOrderActorDTOFactory $logisnapInBulkOrderActorDTOFactory){
        $this->Receiver = $logisnapInBulkOrderActorDTOFactory->create(['data' => $data['Receiver']]);
        $this->Sender = $logisnapInBulkOrderActorDTOFactory->create(['data' => $data['Sender']]);
    }
}