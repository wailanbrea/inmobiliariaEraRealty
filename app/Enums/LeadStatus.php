<?php

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Interested = 'interested';
    case VisitScheduled = 'visit_scheduled';
    case Negotiating = 'negotiating';
    case Closed = 'closed';
    case Lost = 'lost';
    case Spam = 'spam';
}
