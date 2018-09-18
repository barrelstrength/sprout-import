<?php

namespace sproutimport\enums;

/**
 * The ImportType class defines all available import types
 */
abstract class ImportType
{
    // Constants
    // =========================================================================

    /**
     * Seeds generated via a POST data including imports submitted via the CopyPaste option on the Import tab
     */
    const Post = 'Post';

    /**
     * Seeds generated via the Upload Files option on the Import tab
     */
    const File = 'File';

    /**
     * Seeds generated via the Seed tab
     */
    const Seed = 'Seed';

    /**
     * Seeds generated via a Theme integration
     */
    const Theme = 'Theme';

    /**
     * Seeds generated through console
     */
    const Console = 'Console';
}
