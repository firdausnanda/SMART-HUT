<?php

namespace App\Enums;

enum Satuan: string
{
  case KG = 'kg';
  case TON = 'ton';
  case M3 = 'm3';
  case LITER = 'liter';
  case BATANG = 'batang';
  case EKOR = 'ekor';
  case BUAH = 'buah';
  case PCS = 'pcs';

  public function label(): string
  {
    return match ($this) {
      self::KG => 'Kilogram (Kg)',
      self::TON => 'Ton',
      self::M3 => 'Meter Kubik (M³)',
      self::LITER => 'Liter',
      self::BATANG => 'Batang',
      self::EKOR => 'Ekor',
      self::BUAH => 'Buah',
      self::PCS => 'Pcs',
    };
  }
}