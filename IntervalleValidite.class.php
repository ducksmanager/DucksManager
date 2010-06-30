<?php

class IntervalleValidite {
    var $intervalles;

    function IntervalleValidite(array $intervalles=null) {
        $this->intervalles=$intervalles;
    }

    function estValide($numero) {
        if (!is_array($this->intervalles))
            return false;
        if (in_array($numero, $this->intervalles))
            return true;
        foreach($this->intervalles as $intervalle) {
            if (is_array($intervalle))
                if ($numero>=$intervalle['debut'] && $numero <= $intervalle['fin'] && (!array_key_exists('sauf', $intervalle) || !in_array($numero,$intervalle['sauf'])))
                    return true;
        }
        return false;
    }
}
?>