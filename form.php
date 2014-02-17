<?php if ( ! defined( 'ABSPATH' ) ) exit('No direct script access allowed'); // Exit if accessed directly ?>
<div class="wrap">
    <?php echo '<h2>' . __( 'Virtual Pages and Templates Settings' ) . '</h2>';?>  
    <?php 
    $options = get_option('vpt_options');
    $virtualpageurl = '/shop/%postname%';
    $page_template = null;
    $use_custom_permalink_structure = FALSE;
    $affect_search = TRUE;

    if (!empty($options)){
        $virtualpageurl = $options['virtualpageurl'];
        $page_template = $options['page_template'];
        $use_custom_permalink_structure = $options['use_custom_permalink_structure'];
        $affect_search = $options['affect_search'];
    }

    $posts = new WP_Query( array( 'post_status' => array('draft'), 'post_type' => array('post') ) );
    $pages = new WP_Query( array( 'post_status' => array('draft'), 'post_type' => array('page') ) );

    
    $class = 'hidden';
    if (empty($posts->posts) && empty($pages->posts) && !isset($_GET['no-template']) && !isset($_GET['settings-updated']))
    {
        $class = '';    
    }
    
    ?>
    <div class="error no-template-message <?php echo $class;?>" id="message">
        <p>
            <strong>
                Page template is required. You can make a template by creating a <a href="<?php echo admin_url('post-new.php')?>">post</a> or a <a href="<?php echo admin_url('post-new.php?post_type=page')?>">page</a> as save it as draft.
            </strong>
        </p>
    </div>
    <form id="vpt_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" class="validate">
        <input type="hidden" name="vpt_hidden" value="Y"/>  
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row"><label for="virtualpageurl"><?php _e('Virtual Page URL: '); ?></label></th>
                <td>
                    <?php if ($use_custom_permalink_structure) $checked = 'checked="checked"'; else $checked = '';?>
                    <label for="users_can_register"><input type="checkbox" value="1" id="use_custom_permalink_structure" <?php echo $checked;?> name="use_custom_permalink_structure">Use custom permalink structure</label>
                    <div id="use_permalink_label">
                        <p class="description">Virtual pages uses current permalink structure.</p>
                    </div>
                    <div id="use_custom_pageurl">
                    <input type="text" class="regular-text code" value="<?php echo $virtualpageurl?>" id="virtualpageurl" name="virtualpageurl"/>
                    <p class="description">Virtual pages will only be shown on this url.</p>
                    </div>
                </td>
            </tr>
           
            <tr valign="top">
            <th scope="row"><label for="default_role"><?php _e('Page Template: ' ); ?></label></th>
                <td>
                <select id="page_template" name="page_template" style="width: 25em">
                    <?php if (!empty($posts->posts)) :?>
                        <optgroup label="Posts">
                        <?php foreach ($posts->posts as $post):?>
                            <?php if ($page_template == $post->ID) $selected = 'selected="selected"'; else $selected = '';?>
                            <option value="<?php echo $post->ID;?>" <?php echo $selected;?>><?php _e($post->post_title);?></option>
                        <?php endforeach;?>
                        </optgroup>
                    <?php endif;?>
                    <?php if (!empty($pages->posts)) :?>
                        <optgroup label="Pages">
                        <?php foreach ($pages->posts as $page):?>
                            <?php if ($page_template == $page->ID) $selected = 'selected="selected"'; else $selected = '';?>
                            <option value="<?php echo $page->ID;?>" <?php echo $selected;?>><?php _e($page->post_title);?></option>
                        <?php endforeach;?>
                        </optgroup>
                    <?php endif;?>
                </select>
                
                <p class="description">Specify an existing post or page (one that isn’t published) that will be used as a template.</p>
                </td>
            </tr>
   
            <tr valign="top">
            <th scope="row"><?php _e('Affect search result ' ); ?></th>
            <td>
                <?php if ($affect_search) $checked = 'checked="checked"'; else $checked = '';?>
               <label for="affect_search"><input type="checkbox" value="1" id="affect_search" <?php echo $checked;?> name="affect_search"></label>
               <p class="description">Generate virtual page using the searched keyword if there are no pages found</p>
            </td>
            </tr>
            </tbody>
        </table>

        <p class="submit"><input type="submit" value="Save Changes" class="button button-primary" id="submit" name="submit"></p>
    </form>
</div>