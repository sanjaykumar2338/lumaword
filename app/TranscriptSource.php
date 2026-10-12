<?php

namespace App;

enum TranscriptSource: string
{
    case None = 'none';
    case Pasted = 'pasted';
    case Provider = 'provider';
    case Upload = 'upload';
}
