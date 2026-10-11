<?php

namespace App\Messenger\Query;

/**
 * Marker interface for queries.
 *
 * A query reads state without modifying it.
 * It is dispatched through the query.bus.
 */
interface Query
{
}