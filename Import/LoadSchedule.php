<?php

namespace Jet\Modules\Ikosoft\Import;

/**
 * Class LoadSchedule
 * @package Jet\Modules\Ikosoft\Import
 */
class LoadSchedule extends LoadCustomField
{
    /**
     * @var array
     */
    private $theme_callback = [

    ];
    
    /**
     * @param $entry
     * @return array|bool
     */
    public function load($entry)
    {

        $schedules = [];

        foreach ($entry->s as $schedule) {
            $data = [];
            foreach ($schedule->e as $e) {
                $data[(string)$e['n']] = (string)$e['v'];
            }
            $schedules[(int)$schedule['n']] = $data;
        }

        $this->loadScheduleData($schedules);

        return true;
    }

    /**
     * @param array $schedules
     */
    private function loadScheduleData($schedules = [])
    {
        if ($this->hasField($this->import->data['website_id'], 'opening_hours') == true) {
            $this->updateField('opening_hours', [$this, 'getContent'], $schedules);
        } else {
            $this->createField('opening_hours', [$this, 'getContent'], $schedules);
        }
    }
    
    /**
     * @param $content
     * @param array $schedules
     * @return string
     */
    public function getContent($content, $schedules = [])
    {
        $content = is_array($content) ? $content : json_decode($content, true);
        if (isset($content['value'])) {
            if (isset($this->import->data['theme']['name']) && isset($this->theme_callback[$this->import->data['theme']['name']])) {
                $callback = explode('@', $this->theme_callback[$this->import->data['theme']['name']]);
                if (isset($callback[1])) {
                    $content['value'] = $this->import->callMethod($callback[0], $callback[1], compact('schedules'));
                }
            } else {
                $content['value'] = $this->formatSchedule($schedules);
            }
        }
        return json_encode($content);
    }

    /**
     * @param array $schedules
     * @return string
     */
    private function formatSchedule($schedules = [])
    {
        $date = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $string = '';
        foreach ($schedules as $key => $schedule) {
            if (isset($date[$key - 1])) {
                $string .= '<p>' . $date[$key - 1] . ' : ' . date('H:i', mktime(0, $schedule['from'])) . ' - ' . date('H:i', mktime(0, $schedule['to'])) . '</p>';
            }
        }
        return $string;
    }

}