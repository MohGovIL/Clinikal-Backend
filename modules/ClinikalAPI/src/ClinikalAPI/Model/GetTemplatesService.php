<?php

/**
 * Date: 02/06/2020
 *  @author Dror Golan <drorgo@matrix.co.il>
 */


namespace ClinikalAPI\Model;


class GetTemplatesService
{

    public $form_id;
    public $form_field;
    public $service_type;
    public $reason_code;
    public $message_id;
    public $order;
    public $active;

    //lists fields for join
    public $option_id;
    public $title;
    public $seq;



    public function exchangeArray($data)
    {
        $this->form_id = (!empty($data['form_id'])) ? $data['form_id'] : null;
        $this->form_field = (!empty($data['form_field'])) ? $data['form_field'] : null;
        $this->service_type = (!empty($data['service_type'])) ? $data['service_type'] : null;
        $this->reason_code = (!empty($data['reason_code'])) ? $data['reason_code'] : null;
        $this->message_id = (!empty($data['message_id'])) ? $data['message_id'] : null;
        $this->order = (!empty($data['seq'])) ? $data['seq'] : null;
        $this->option_id = (!empty($data['option_id'])) ? $data['option_id'] : null;
        $this->title = (!empty($data['title'])) ? $data['title'] : null;
        $this->seq = (!empty($data['seq'])) ? $data['seq'] : null;
        $this->active = (!empty($data['active'])) ? $data['active'] : null;
    }
}


