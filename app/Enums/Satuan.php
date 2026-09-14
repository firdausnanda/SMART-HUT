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
  case IKAT = 'ikat';
  case BIBIT = 'bibit';
  case STUP = 'stup';
  case ORANG = 'orang';
  case BUTIR = 'butir';
  case LAINNYA = 'lainnya';

  public function label(): string
  {
    return match ($this) {
      self::KG => 'Kilogram (Kg)',
      self::TON => 'Ton',
      self::M3 => 'Meter Kubik (M3)',
      self::LITER => 'Liter',
      self::BATANG => 'Batang',
      self::EKOR => 'Ekor',
      self::BUAH => 'Buah',
      self::PCS => 'Pcs',
      self::IKAT => 'Ikat',
      self::BIBIT => 'Bibit',
      self::STUP => 'Stup',
      self::ORANG => 'Orang',
      self::BUTIR => 'Butir',
      self::LAINNYA => 'Lainnya'
    };
  }
}