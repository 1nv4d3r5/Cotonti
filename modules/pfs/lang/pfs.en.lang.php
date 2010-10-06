<?php
/**
 * English Language File for the PFS Module (pfs.en.lang.php)
 *
 * @package pfs
 * @version 0.7.0
 * @author Cotonti Translators Team
 * @copyright Copyright (c) Cotonti Team 2008-2009
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL.');

/**
 * Admin
 */

$L['adm_gd'] = 'GD graphical library';
$L['adm_allpfs'] = 'All '.$L['PFS'];
$L['adm_allfiles'] = 'All files';
$L['adm_thumbnails'] = 'Thumbnails';
$L['adm_orphandbentries'] = 'Orphan DB entries';
$L['adm_orphanfiles'] = 'Orphan files';
$L['adm_delallthumbs'] = 'Delete all thumbnails';
$L['adm_rebuildallthumbs']= 'Delete and rebuild all thumbnails';
$L['adm_help_allpfs'] = $L['PFS'].' of all registered users';
$L['adm_nogd'] = 'The GD graphical library is not supported by this host, Cotonti won\'t be able to create thumbnails for images. Go for '.$L['Configuration'].' &gt; '.$L['PFS'].' and set &quot;Thumbnails generation&quot; to &quot;'.$L['Disabled'].'&quot;.';

$L['adm_help_pfsfiles'] = 'Not available';
$L['adm_help_pfsthumbs'] = 'Not available';

/**
 * Config
 */

$L['cfg_maxpfsperpage'] = array('Max. elements on page', ' ');
$L['cfg_pfsfilecheck'] = array('File Check', 'If Enabled will check any uploaded files through the '.$L['PFS'].', or images through the profile. To insure they are valid files. &quot;Yes&quot; recommended, for security reasons.');	// New in 0.0.2
$L['cfg_pfsnomimepass'] = array('No Mimetype Pass', 'If Enabled will it will allow uploaded files to pass even if there is no mimetype in the config file.');	// New in 0.0.2
$L['cfg_pfstimename'] = array('Time-based filenames', 'Generate filenames based on current time stamp. By default the original file name is used with some necessary character conversions.');	// New in 0.0.2
$L['cfg_pfsuserfolder'] = array('Folder storage mode', 'If enabled, will store the user files in subfolders /datas/users/USERID/FOLDERNAME/... Must be set at the FIRST setup of the site ONLY. As soon as a file is uploaded, it\'s too late to change this.');
$L['cfg_flashupload'] = array('Use flash uploader', 'Allows uploading many files at once.'); // New in 1.0.0
$L['cfg_pfs_winclose'] = array('Close popup window after bbcode insertion');
$L['cfg_th_amode'] = array('Thumbnails generation', '');
$L['cfg_th_border'] = array('Thumbnails, border size', 'Default: 4 pixels');
$L['cfg_th_colorbg'] = array('Thumbnails, border color', 'Default: 000000, hex color code');
$L['cfg_th_colortext'] = array('Thumbnails, text color', 'Default: FFFFFF, hex color code');
$L['cfg_th_dimpriority'] = array('Thumbnails, rescaling priority dimension', '');
$L['cfg_th_jpeg_quality'] = array('Thumbnails, Jpeg quality', 'Default: 85');
$L['cfg_th_keepratio'] = array('Thumbnail, keep ratio?', '');
$L['cfg_th_textsize'] = array('Thumbnails, size of the text', '');
$L['cfg_th_x'] = array('Thumbnails, width', 'Default: 112 pixels');
$L['cfg_th_y'] = array('Thumbnails, height', 'Default: 84 pixel, recommended: Width x 0.75');

/**
 * Main
 */

$L['pfs_cancelall'] = 'Cancel All'; /// 0.7.0
$L['pfs_direxists'] = 'Such a folder already exists.<br />Old path: %1$s<br />New path: %2$s'; // 0.7.0
$L['pfs_extallowed'] = 'Extensions allowed';
$L['pfs_filecheckfail'] = 'Warning: File Check Failed for Extension: %1$s Filename - %2$s';	// New in N-0.0.2
$L['pfs_filechecknomime'] = 'Warning: No Mime Type data was found for the Extension: %1$s Filename - %2$s';	// New in N-0.0.2
$L['pfs_fileexists'] = 'The upload failed, there\'s already a file with this name?';
$L['pfs_filelistempty'] = 'List is empty.';
$L['pfs_filemimemissing'] = 'The mime type for %1$s is missing. Upload Failed';	// New in N-0.0.2
$L['pfs_filenotmoved'] = 'The upload failed, temporary file cannot be moved.';	// New in 0.0.3
$L['pfs_filenotvalid'] = 'This is not a valid %1$s file.';	// New in N-0.0.2
$L['pfs_filesintheroot'] = 'File(s) in the root';
$L['pfs_filesinthisfolder'] = 'File(s) in this folder';
$L['pfs_filetoobigorext'] = 'The upload failed, this file is too big maybe, or the extension is not allowed?';
$L['pfs_folderistempty'] = 'This folder is empty.';
$L['pfs_foldertitlemissing'] = 'A folder title is required.';
$L['pfs_isgallery'] = 'Gallery?';
$L['pfs_ispublic'] = 'Public?';
$L['pfs_maxsize'] = 'Maximum size for a file';
$L['pfs_maxspace'] = 'Maximum space allowed';
$L['pfs_newfile'] = 'Upload a file:';
$L['pfs_newfolder'] = 'Create a new folder:';
$L['pfs_onpage'] = 'On this page'; // 0.7.0
$L['pfs_parentfolder'] = 'Parent folder'; // 0.7.0
$L['pfs_pastefile'] = 'Paste as file link'; // 0.7.0
$L['pfs_pasteimage'] = 'Paste as image'; // 0.7.0
$L['pfs_pastethumb'] = 'Paste as thumbnail'; // 0.7.0
$L['pfs_resizeimages'] = 'to scale the image?';	// New in N-0.0.2
$L['pfs_title'] = 'My Personal File Space';
$L['pfs_totalsize'] = 'Total size';
$L['pfs_uploadfiles'] = 'Upload Files'; // 0.7.0

?>