<?php

namespace App;

enum SermonSourceType: string
{
    case Url = 'url';
    case Transcript = 'transcript';
    case Upload = 'upload';
}
