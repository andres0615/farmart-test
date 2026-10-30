<?php

namespace App\Enums;

// Tipos de movimiento del kardex. ENTRADA y SALIDA llevan quantity con signo;
// AJUSTE exige reason en la capa de aplicación.
enum StockMovementType: string
{
    case ENTRADA = 'ENTRADA';
    case SALIDA = 'SALIDA';
    case AJUSTE = 'AJUSTE';
    case DISPENSACION = 'DISPENSACION';
    case TRANSFERENCIA = 'TRANSFERENCIA';
}
