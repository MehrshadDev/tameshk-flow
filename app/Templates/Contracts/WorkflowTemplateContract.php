<?php

namespace App\Templates\Contracts;

use App\Templates\DTOs\WorkflowTemplate;

interface WorkflowTemplateContract
{
    public static function make(): WorkflowTemplate;
}
