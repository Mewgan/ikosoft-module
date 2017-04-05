<?php

namespace Jet\Modules\Ikosoft\Import;

/**
 * Class LoadBookingLink
 * @package Jet\Modules\Ikosoft\Import
 */
class LoadBookingLink extends LoadCustomField
{

    /**
     * @return array|bool
     */
    public function load()
    {
        $this->loadBookingLink();
        return true;
    }

    /**
     *
     */
    private function loadBookingLink()
    {
        if ($this->hasField($this->import->data['website_id'], 'booking_link') == true) {
            $this->updateField('booking_link', [$this, 'getContent']);
        } else {
            $this->createField('booking_link', [$this, 'getContent']);
        }
    }
    
    /**
     * @param $content
     * @return string
     */
    public function getContent($content)
    {
        $content = is_array($content) ? $content : json_decode($content, true);
        if (isset($content['value'])) 
            $content['value'] = 'http://www.ikosoft.com/BookingOnLine/index.asp?IDSalon=' . $this->import->data['instance'] . '&amp;Lang=FRA';
        return json_encode($content);
    }

}