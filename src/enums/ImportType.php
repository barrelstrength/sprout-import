<?php

namespace sproutimport\enums;

/**
 * The ImportType class defines all available import types
 */
abstract class ImportType
{
    // Constants
    // =========================================================================

    const CopyPaste = 'CopyPaste';
    const File = 'File';
    const Post = 'Post';
    const Theme = 'Theme';
}
