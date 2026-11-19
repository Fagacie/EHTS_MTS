<?php

namespace App\Enums;

enum OrderItemType: string
{
    case TrainingSession = 'training_session';
    case Module = 'module';
}
