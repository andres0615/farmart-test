<?php

namespace App\Enums;

// Catálogo de estados de traslado. Los valores distintos de BORRADOR
// están pendientes de confirmar contra los requisitos.
enum TransferStatus: string
{
    case BORRADOR = 'BORRADOR';
    case ENVIADO = 'ENVIADO';
    case RECIBIDO = 'RECIBIDO';
    case ANULADO = 'ANULADO';
}
