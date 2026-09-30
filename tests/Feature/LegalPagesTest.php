<?php

test('legal pages are public', function (string $page) {
    $this->get("/legal/{$page}")->assertOk();
})->with(['terms', 'privacy', 'cookies', 'refunds']);

test('unknown legal pages return 404', function () {
    $this->get('/legal/unknown')->assertNotFound();
});
