<?php

namespace App\Messenger\Command;

/**
 * Marker interface for command handlers.
 *
 * Each CommandHandler is auto-tagged in services.yaml so that Symfony Messenger
 * routes the matching Command to its handler.
 */
interface CommandHandler
{
}