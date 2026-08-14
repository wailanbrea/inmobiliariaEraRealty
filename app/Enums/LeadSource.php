<?php

namespace App\Enums;

enum LeadSource: string
{
    case ContactPage = 'contact_page';
    case PropertyDetail = 'property_detail';
    case PublishProperty = 'publish_property';
    case InvestmentPage = 'investment_page';
    case WhatsappClick = 'whatsapp_click';
    case NewsContact = 'news_contact';
}
