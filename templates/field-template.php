<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div>
    <label>
        <input class="<?php echo $vars['class']; ?>"
               type="<?php  echo $vars['type']; ?>"
               name="<?php  echo $vars['name']; ?>"
               value="<?php echo $vars['value']; ?>"
               <?php echo $vars['checked']; ?> /> <?php echo $vars['label']; ?>
    </label>
</div>