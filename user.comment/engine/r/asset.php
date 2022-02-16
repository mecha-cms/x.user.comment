<?php

// Loading asset(s)…
Hook::set('content', function() {
    $state = State::get(null, true);
    if (!empty($state['is']['page']) && !empty($state['has']['page'])) {
        $path = __DIR__ . DS . '..' . DS . '..' . DS . 'lot' . DS . 'asset' . DS;
        $z = defined('DEBUG') && DEBUG ? '.' : '.min.';
        Asset::set($path . 'css' . DS . 'index' . $z . 'css', 10);
    }
}, -1);