<script type="text/html" id="toolset-video-template">
    <# var wrap_height = parseInt(height) + 82;#>
<div class="toolset-box-container js-toolset-box-container" style="width:{{{width}}};height:{{{wrap_height}}}px;">
    <div class="toolset-box toolset-box-video js-video-player-box hidden" id="js-video-player-box">
        <div class="toolset-box-header">
            <h2 class="js-video-box-title video-box-title-open js-video-box-title-open">{{{title}}}
                <i class="fa fa-file-video-o toolset-video-icon"></i>
            </h2>

        </div>

        <div class="toolset-box-content js-toolset-box-content">
            <div class="js-video-container" style="width:{{{width}}};height:{{{height}}};">
                <video class="js-video-player" src="{{{url}}}" width="{{{width}}}" height="{{{height}}}"/>
            </div>
        </div> <!-- .toolset-box-content -->

        <div class="toolset-box-footer">
            <span><a href="https://www.surveymonkey.com/r/layouts-videos"><?php _e('Give us feedback about this video', 'ddl-layouts');?></a></span>
            <button class="button js-edit-video-close js-remove-video remove-video"><?php _e('Minimize','ddl-layouts') ?></button>
            <button class="button js-edit-video-detach js-detach-video remove-video"><?php _e('Open in a new window','ddl-layouts') ?></button>
        </div>

    </div> <!-- .toolset-box -->

</div> <!-- .toolset-boxs-container" -->
</script>

<script type="text/html" id="toolset-video-list-template">
    {{{title}}}<i class="fa fa-file-video-o toolset-video-icon"></i>
</script>

<script type="text/html" id="toolset-video-header-template">
        <h2 class="js-video-box-title video-box-title">{{{title}}}
            <i class="fa fa-file-video-o toolset-video-icon"></i>
            <i class="fa fa-caret-square-o-up toolset-video-closed js-edit-dialog-close remove-video-icon"></i>
        </h2>
</script>