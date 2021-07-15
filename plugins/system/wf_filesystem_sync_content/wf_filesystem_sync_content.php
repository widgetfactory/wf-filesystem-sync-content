<?php

/**
 * @copyright   Copyright (C) 2021 Ryan Demmer. All rights reserved
 * @license     GNU General Public License version 2 or later
 */
defined('JPATH_BASE') or die;

class PlgSystemWf_Filesystem_Sync_Content extends JPlugin
{
    private function makeRelative($path)
    {
        if (function_exists('mb_substr')) {
            $path = mb_substr($path, mb_strlen(JPATH_SITE));
        } else {
            $path = substr($path, strlen(JPATH_SITE));
        }

        $path = preg_replace('#[/\\\\]+#', '/', $path);

        return ltrim($path, '/');
    }
    
    private function searchAndReplaceFilename($before, $after)
    {
        // make the paths relative, eg: images/forest.jpg
        $before = $this->makeRelative($before);
        $after = $this->makeRelative($after);

        $db = JFactory::getDBO();

        $query = $db->getQuery(true);

        // search for attribute value allowing for folder names
        $word = $db->quote('%="' . $db->escape($before, true) . '%', false);

        $query->select('id, introtext')->from('#__content')->where('introtext LIKE ' . $word . '');
        $db->setQuery($query);

        $rows = $db->loadObjectList();

        $table = JTable::getInstance('Content', 'JTable');

        foreach ($rows as $row) {
            $row->introtext = preg_replace('#(src|poster|url|srcset|data)="' . preg_quote($before, '#') . '([^"]*)"#', '$1="' . $after . '$2"', $row->introtext);

            if ($table->load($row->id)) {
                $table->introtext = $row->introtext;
                $table->store();
            }
        }
    }

    public function onWfFileSystemAfterRename($result)
    {
        if ($result->state) {
            $before = $result->source;
            $after = $result->path;

            $this->searchAndReplaceFilename($result->source, $result->path);
        }
    }

    public function onWfFileSystemAfterMove($result)
    {
        if ($result->state) {
            $before = $result->source;
            $after = $result->path;

            $this->searchAndReplaceFilename($result->source, $result->path);
        }
    }
}