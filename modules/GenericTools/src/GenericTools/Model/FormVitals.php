<?php


namespace GenericTools\Model;


class FormVitals
{

    public $id;
    public $date;
    public $pid;
    public $user;
    public $activity;
    public $bps;
    public $bpd;
    public $weight;
    public $height;
    public $temperature;
    public $temp_method;
    public $pulse;
    public $respiration;
    public $note;
    public $BMI;
    public $BMI_status;
    public $waist_circ;
    public $head_circ;
    public $oxygen_saturation;
    public $glucose;
    public $pain_severity;
    public $eid;
    public $category;



    public function exchangeArray($data)
    {
        $this->id = (!empty($data['id'])) ? $data['id'] : null;
        $this->date = (!empty($data['date'])) ? $data['date'] : null;
        $this->pid = (!empty($data['pid'])) ? $data['pid'] : null;
        $this->user = (!empty($data['user'])) ? $data['user'] : null;
        $this->activity = (!empty($data['activity'])) ? $data['activity'] : null;
        $this->bps = (!empty($data['bps'])) ? $data['bps'] : null;
        $this->bpd = (!empty($data['bpd'])) ? $data['bpd'] : null;
        $this->weight = (!empty($data['weight'])) ? $data['weight'] : null;
        $this->height = (!empty($data['height'])) ? $data['height'] : null;
        $this->temperature = (!empty($data['temperature'])) ? $data['temperature'] : null;
        $this->temp_method = (!empty($data['temp_method'])) ? $data['temp_method'] : null;
        $this->pulse = (!empty($data['pulse'])) ? $data['pulse'] : null;
        $this->respiration = (!empty($data['respiration'])) ? $data['respiration'] : null;
        $this->note = (!empty($data['note'])) ? $data['note'] : null;
        $this->BMI = (!empty($data['BMI'])) ? $data['BMI'] : null;
        $this->BMI_status = (!empty($data['BMI_status'])) ? $data['BMI_status'] : null;
        $this->waist_circ = (!empty($data['waist_circ'])) ? $data['waist_circ'] : null;
        $this->head_circ = (!empty($data['head_circ'])) ? $data['head_circ'] : null;
        $this->oxygen_saturation = (!empty($data['oxygen_saturation'])) ? $data['oxygen_saturation'] : null;
        $this->glucose = (!empty($data['glucose'])) ? $data['glucose'] : null;
        $this->pain_severity = (!is_null($data['pain_severity'])) ? $data['pain_severity'] : null;
        $this->eid = (!empty($data['eid'])) ? $data['eid'] : null;
        $this->category = (!empty($data['category'])) ? $data['category'] : null;
    }
}
