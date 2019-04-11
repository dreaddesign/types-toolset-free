var DDLayout = DDLayout || {};

DDLayout.DialogView = Backbone.View.extend({

    initialize: function ( options ) {
        var self = this,
            settings = {
                escape: /\{\{([^\}]+?)\}\}(?!\})/g,
                evaluate: /<#([\s\S]+?)#>/g,
                interpolate: /\{\{\{([\s\S]+?)\}\}\}/g
            },
            template_settings = _.defaults({}, settings, _.templateSettings);

        _.defaults(options, {
                    title: "",
                    template_object: {},
                    selector:'#ddl-default-edit',
                    buttons:[],
                    autoOpen: true,
                    show: true,
                    dialogClass: 'ddl-dialogs-container',
                    position: {my: "center top+40", at: "center top+40", of: window},
                    modal: true,
                    width: 850,
                    height: "auto",
                    maxHeight: 1000,
                    maxWidth: 800,
                    minHeight: "auto",
                    minWidth: 600,
                    resizable: false,
                    draggable: false,
                    closeText:'',
                });

        _.bindAll(self, "render", 'open', 'create', 'close', 'beforeOpen', 'beforeClose', 'focus', 'refresh');

        self.dialog_name = options && options.name ? options.name : 'default';

        self.set_template_object( options.template_object );

        self.set_title( options.title );
        self.template = _.template( jQuery( options.selector ).html(), template_settings );

        self.listenTo(self.eventDispatcher, 'ddldialogopen'+'-'+self.dialog_name, self.open);
        self.listenTo(self.eventDispatcher,'ddldialogcreate'+'-'+self.dialog_name, self.create);
        self.listenTo(self.eventDispatcher,'ddldialogclose'+'-'+self.dialog_name, self.close);
        self.listenTo(self.eventDispatcher,'ddldialogbeforeopen'+'-'+self.dialog_name, self.beforeOpen);
        self.listenTo(self.eventDispatcher,'ddldialogbeforeclose'+'-'+self.dialog_name, self.beforeClose);
        self.listenTo(self.eventDispatcher,'ddldialogfocus'+'-'+self.dialog_name, self.focus);
        self.listenTo(self.eventDispatcher,'ddldialogrefresh'+'-'+self.dialog_name, self.refresh);

        self.render(options).el;
    },
    render: function (options) {
        var self = this;

        self.$el.html( self.template( self.get_template_object() ) );

        self.init_dialog(options);

        return self;
    },
    init_dialog:function(options){
        var self = this;
        self.$el.ddldialog({
            title: self.get_title(),
            autoOpen: options.autoOpen,
            show: options.show,
            dialogClass: options.dialogClass,
            position: options.position,
            modal: options.modal,
            width: options.width,
            height: options.height,
            maxHeight: options.maxHeight,
            maxWidth: options.maxWidth,
            minHeight: options.minHeight,
            minWidth: options.minWidth,
            resizable: options.resizable,
            draggable: options.draggable,
            closeText:options.closeText,
            buttons:options.buttons,
            beforeOpen: function (event) {
                self.eventDispatcher.trigger(event.type+'-'+self.dialog_name, event, this, self);
            },
            beforeClose: function (event) {
                self.eventDispatcher.trigger(event.type+'-'+self.dialog_name, event, this, self);
            },
            close: function (event) {
                self.eventDispatcher.trigger(event.type+'-'+self.dialog_name, event, this, self);
                self.stopListening(self.eventDispatcher, 'ddldialogopen'+'-'+self.dialog_name, self.open);
                self.stopListening(self.eventDispatcher,'ddldialogcreate'+'-'+self.dialog_name, self.create);
                self.stopListening(self.eventDispatcher,'ddldialogclose'+'-'+self.dialog_name, self.close);
                self.stopListening(self.eventDispatcher,'ddldialogbeforeopen'+'-'+self.dialog_name, self.beforeOpen);
                self.stopListening(self.eventDispatcher,'ddldialogbeforeclose'+'-'+self.dialog_name, self.beforeClose);
                self.stopListening(self.eventDispatcher,'ddldialogfocus'+'-'+self.dialog_name, self.focus);
                self.stopListening(self.eventDispatcher,'ddldialogrefresh'+'-'+self.dialog_name, self.refresh);
            },
            create: function (event) {
                self.eventDispatcher.trigger(event.type+'-'+self.dialog_name, event, this, self);
            },
            focus: function (event) {
                self.eventDispatcher.trigger(event.type+'-'+self.dialog_name, event, this, self);
            },
            refresh: function (event) {
                self.eventDispatcher.trigger(event.type+'-'+self.dialog_name, event, this, self);
            },
            open:function( event ){
                self.eventDispatcher.trigger(event.type+'-'+self.dialog_name, event, this, self);
            }
        });
    },
    open: function ( event, dom, view ) {
        //console.log(event.type, arguments);
    },
    beforeOpen: function ( event, dom, view ) {
        //console.log(event.type, arguments);
    },
    beforeClose: function (event, dom, view) {
       //console.log(event.type, arguments);
    },
    close: function ( event, dom, view ) {
        //console.log(event.type, arguments);
    },
    create: function ( event, dom, view ) {
       //console.log(event.type, arguments);

    },
    focus: function ( event, dom, view ) {
      // console.log(event.type, arguments);

    },
    refresh: function ( event, dom, view ) {
        //console.log(event.type, arguments);
    },
    get_template_object:function(){
        return this.template_object;
    },
    set_template_object:function( object ){
        this.template_object = _.extend( {type:"", header:"", content:""}, object );
    },
    set_title:function( title ){
        this.title = title;
    },
    get_title:function(){
        return this.title;
    },
    dialog_close:function(){
        var self = this;
        self.$el.ddldialog('close');
    },
    dialog_open:function(){
        var self = this;
        self.$el.ddldialog('open');
    }
});