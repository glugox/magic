<?php

namespace Glugox\Magic\Validation\RuleSet;

/**
 * We want different rulesets for different contexts, e.g. when creating vs updating an entity
 */
class RuleSetCategory
{
    const CREATE = 'create';
    const UPDATE = 'update';

}
