<?php

use Glugox\Magic\Actions\Build\Components\GenerateVueFormFile;
use Glugox\Magic\Support\File\VueFile;

use function Glugox\Magic\Tests\Helpers\makeDummyUserEntityConfig;

it('generates a Vue form file for a simple schema', function () {
    $entity = makeDummyUserEntityConfig();

    $action = new GenerateVueFormFile;
    $file = $action($entity);
    $content = (string) $file;

    expect($file)->toBeInstanceOf(VueFile::class)
        ->and($file->fileName)->toBe('UserForm.vue')
        ->and($content)->toContain('<script setup lang="ts">')
        ->and($content)->toContain('<template>')
        ->and($content)->toContain('@submit.prevent')
        ->and($content)->toContain('const form = ref')
        ->and($content)->toContain('name: null')
        ->and($content)->toContain('email: null')
        ->and($content)->toContain('is_active: false')
        ->and($content)->toContain('v-model="form.name"')
        ->and($content)->toContain('v-model="form.email"')
        ->and($content)->toContain('v-model="form.is_active"')
        ->and($content)->toContain('<input')
        ->and($content)->toContain('type="email"')
        ->and($content)->toContain('type="checkbox"');

    // script + template scaffold

    // reactive form with defaults

    // fields rendered

    // smart input types
    // name defaults to text
    // email inferred from rule
    // boolean -> checkbox
});

it('includes a submit handler and a basic button', function () {
    $entity = makeDummyUserEntityConfig();

    $file = new GenerateVueFormFile($entity);
    $content = (string) $file;

    expect($content)->toContain('function submit(')
        ->and($content)->toContain('<button type="submit">');
});

// TODO: We need to get our own Vue select component for relations
/*it('renders relations as selects (single vs multi)', function () {
    $entity = makeDummyEntity();

    $file = new GenerateVueFormFile()($entity);
    $content = (string) $file;

    // belongsTo -> single select
    expect($content)->toMatch('/<select[^>]*v-model="form\.author"[^>]*>/')
        ->and($content)->toMatch('/<select[^>]*v-model="form\.tags"[^>]*multiple/')
        ->and($content)->toContain('v-model="form.title"');

    // belongsToMany -> multiselect

    // shows fields + relations in template
});*/
