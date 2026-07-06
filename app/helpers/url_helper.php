<?php

function url(string $path = ''): string
{
    return BASE_URL . '/' . trim($path, '/');
}

function asset(string $path = ''): string
{
    return BASE_URL . '/public/assets/' . trim($path, '/');
}