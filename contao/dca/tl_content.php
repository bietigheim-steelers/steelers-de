<?php

// wrapper
$GLOBALS['TL_DCA']['tl_content']['palettes']['rowStart'] = '{type_legend},type;{link_legend},headline;{expert_legend:hide},cssID;{template_legend:hide},customTpl;';
$GLOBALS['TL_DCA']['tl_content']['palettes']['rowStop'] = '{type_legend},type;{template_legend:hide},customTpl;';
$GLOBALS['TL_DCA']['tl_content']['palettes']['text'] = '{type_legend},type,headline;{text_legend},text;{link_legend},url,target,linkTitle,embed,titleText,rel;{image_legend},addImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['template'] = '{type_legend},type,headline;{template_legend},data,customTpl;{image_legend},addImage;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['fields']['url']['eval']['mandatory'] = false;