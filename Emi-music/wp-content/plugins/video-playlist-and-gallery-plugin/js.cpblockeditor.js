/* This section of the code registers a new block, sets an icon and a category, and indicates what type of fields it'll include. */
var cp_user_status = false;
var cp_doing_ajax_reqiest = false;
var cp_token = cincopa_cp_mt_options.api_token;
var cincopa_defaults = cincopa_cp_mt_options.cincopa_defaults
var cp_site_url = cincopa_cp_mt_options.site_url;

var that = this;
var isToken = false;
if(cp_token=="undefined" || !cp_token) {
  isToken = false;
}

function checkLoginStatus(options) {
  if (cp_user_status) {
    if (typeof options.success === "function") {
      setTimeout(function () {
        options.success({
          success: true
        })
      }, 0)

    }
    return;
  }
  if (cp_doing_ajax_reqiest) {
    if (typeof options.error === "function") {
      setTimeout(function () {
        options.success({
          success: false
        })
      }, 0);
    }
    return;
  }

  cp_doing_ajax_reqiest = true;
  jQuery.ajax({
    url: 'https://api.cincopa.com/v2/ping.json?api_token=' + cp_token,
    type: options.type ? options.type : 'GET',
    dataType: options.dataType ? options.dataType : 'jsonp',
    success: function (data) {

      if (data.success) {
        cp_user_status = true;
      } else {
        cp_user_status = false;
      }
      if (typeof options.success === "function") {
        options.success(data)
      }
    },
    error: function (err) {
      cp_user_status = false;
      if (typeof options.error === "function") {
        options.error(err)
      }
    },
    complete: function () {
      cp_doing_ajax_reqiest = false;
    }
  })


}

//that.hideEmbed = false;


Preview.prototype = Object.create(React.Component.prototype);

function Preview(props) {
  React.Component.constructor.call(this);
  var self = this;
  self.componentDidUpdate = function (prevProps) {
    var idToRender = self.props.savedCode.split("!")[1];
    if (prevProps.embedId != self.props.embedId) {
      appendEmbedCode();
    }
  }
  self.componentDidMount = function () {
    appendEmbedCode();
  }


  self.getRenderId = function (props) {
    var idToRender;
    if (!props.embedId) {
      idToRender = (props.savedCode.replace('[cincopa', '').replace(']', '')).trim();
    } else if (props.embedType == 'gallery') {
      idToRender = props.embedId;
    } else {
      idToRender = props.defaults[props.embedType] + '!' + props.embedId;
    }
    return idToRender;
  }

  function appendEmbedCode() {
    var idToRender = self.getRenderId(self.props);

    if (!idToRender) {
      return;
    }
    var allcontainers = document.querySelectorAll('.gallerydemo:not(.cp-gallery-activated)');

    allcontainers.forEach(function(container) {
      container.innerHTML = '';
      if(window.cincopa){
        setTimeout(function() {
          window.cincopa.boot_all();
        },100);
      }
    });  
    if (!document.getElementById('cplibasyncjs')) {
      var libasyncjs = document.createElement('script');
      libasyncjs.id = 'cplibasyncjs';
      libasyncjs.setAttribute(
        'src',
        'https://rtcdn.cincopa.com/libasync.js');
        document.body.appendChild(libasyncjs);
    }
          
  }


  self.render = function () {
    var idToRender = self.getRenderId(self.props) || self.props.savedCode.split("!")[1];

    if (!idToRender) {
      return null;
    } else {
      return /*#__PURE__*/ React.createElement("figure", null, /*#__PURE__*/ React.createElement("div", {
          //id: `cincopa_${idToRender.replace('!','_')}`,
          className: 'gallerydemo cincopa-fadein cincopa-fid-'+idToRender
        },
        '[cincopa ' + idToRender + ']'
      ));
    }
  }
}
var defaultEmbed = {};

LibraryEditor.prototype = Object.create(React.Component.prototype);

function LibraryEditor(props) {
  var self = this;


  self.state = {
    show: false,
    showInputsIframe: false,
    rid: props?.wpprops?.attributes?.content?.split("!")[1] || '',
    fid: !props?.wpprops?.attributes?.content?.includes("!") ? props?.wpprops?.attributes?.content : ''
  };

  var tokenPopup = document.createElement('div');
    tokenPopup.className = 'token-popup'
    tokenPopup.innerHTML = `<div class="token-popup-header">
                              <a class="token-popup-header-close">
                                <img src="//wwwcdn.cincopa.com/_cms/design18/images/close.svg">
                              </a>
                            </div>
                            <p>Connect with Cincopa to use this feature</p>
                            <a href="${cp_site_url}/wp-admin/options-general.php?page=cincopaoptions" class="cp_button cp_button-connect">Connect</a>
                            `

  self.openLibraryEditor = function () {
    if( !self.props.isLoggedin && !isToken && !document.querySelector(".token-popup") ){
      document.body.append(tokenPopup);
      document.querySelector("#wpwrap").classList.add('cp-disabled')

      tokenPopup.querySelector("a").onclick = () => {
        tokenPopup.remove();
        document.querySelector("#wpwrap").classList.remove('cp-disabled')
      }
    }else{
      self.setState(prev => ({...prev,show:true, showInputsIframe: false}))
      window.addEventListener("message", receiveMessage, false);
    }
  }

  self.showInputsIframe = function () {
    self.setState(prev => ({...prev,showInputsIframe:true,editorBlock:false,show:false}))
  }
  self.hideInputsIframe = function () {
    self.setState(prev => ({...prev,showInputsIframe:false}))
  }
  self.showCincopaEditorBlock = function () {
    self.setState(prev => ({...prev,editorBlock:true}))
  }
  self.closeCincopaEditorBlock = function () {
    self.setState(prev => ({...prev,editorBlock:false}))
  }
  self.showIncorrectRid = function () {

    self.setState(prev => ({...prev,showIncorrectRid:true}))

  }

  self.changeAssetRIDInput = function (value) {
    self.setState(prev => ({...prev,rid: value,fid:''}))
  }

  self.changeGalleryFIDInput = function (value) {
    self.setState(prev => ({...prev,fid: value,rid:''}))

  }


  self.closeLibraryEditor = function () {
    self.setState({
      show: false
    })
    window.removeEventListener("message", receiveMessage, false);
  }

  self.componentDidUpdate = function (prevProps) {

  }

  

  

  function receiveMessage(event) {

    if (event.data && event.data.sender == 'cincopa-assets-iframe' && self.props.wpprops.isSelected) {
      if (event.data.action == 'insertedItem' || event.data.action == 'insertedGallery') {
        defaultEmbed = event.data.defaults;
        self.closeLibraryEditor();
        setTimeout(() => {self.props.updatePreview(event.data)},100)
        //self.props.updatePreview(event.data);
      }
    }
  }

  self.render = function () {
    var InspectorControls = wp.editor.InspectorControls;
    var PanelBody = wp.components.PanelBody;

    var inputValAsset = null;
    if (self.state.rid != null) {
      inputValAsset = self.state.rid
    } else {
      inputValAsset = self?.props?.wpprops?.attributes?.content?.split("!")[1] || '';
    }

    var inputValGallery = null;
    if (self.state.fid != null) {
      inputValGallery = self.state.fid;
    } else if (self.props && self.props.wpprops && self.props.wpprops.attributes && self.props.wpprops.attributes.content) {
      if (!self.props.wpprops.attributes.content.includes("!")) {
        inputValGallery = self?.props?.wpprops?.attributes?.content;
      }
    }

    
    return React.createElement("div", {
        className: "library-editor-block" + (isToken ? '' : ' insert-short-code-disabled'),
        // style: {"padding": "50px 5px"}
      },
        React.createElement("div", {
          className: '',

        },
        React.createElement("div",{
          className: "cp_insert-from-cincopa-header"
        },
          React.createElement("div",{
            className: "cp_insert-from-cincopa-title"
          },"Cincopa Video & Image Gallery"),
          React.createElement("div",{
            className: "cp_insert-from-cincopa-subtitle"
          },"Showcase your professionally designed video and image gallery.")
        ),
        React.createElement("div",{
          className: "cp_insert-from-cincopa"
        },
          React.createElement("a", {
            className: 'cp_button cp_button-insert-from-cincopa',
            onClick: self.openLibraryEditor
          }, "Insert from Cincopa"),
          React.createElement("a", {
            className: 'cp_button cp_button-short-code',
            onClick: () => {
              if(!self.state.showInputsIframe) {
                self.showInputsIframe()
              } else {
                self.hideInputsIframe()
              }
            } 
          }, "Insert Short Code"),
        ),
        
        React.createElement("p", {
          className: "cp-incorrect-token"
        }, 'incorrect token'),
        
      ),
      React.createElement("div", {
          className: "library-editor-block__iframe-cnt",
          style: {
            'display': !self.state.show ? 'none' : 'flex'
          }
      },
        React.createElement("iframe", {
          src: 'https://www.cincopa.com/media-platform/api/library-editor.aspx?disable_editor=y&api_token=' + cp_token,
          className: "library-editor-block__iframe",
          allow: "microphone *; camera *; display-capture *",
        }),
        React.createElement("div", { 
          className: "library-editor-close-button",  
          onClick: () => {
            self.closeLibraryEditor();
          }
        },
          React.createElement("img", { 
            src: "//wwwcdn.cincopa.com/_cms/design18/images/close.png"
          },)
          ),
      ),

      React.createElement("div", {
        className: "library-editor-block__iframe insert-short-code",
        style: {
          'display': !self.state.showInputsIframe ? 'none' : 'flex'
        }
      },
      React.createElement("div", {
        className: "insert-short-code-header",
      },
        React.createElement("div", { 
          className: "library-editor-close-button",  
          onClick: () => {
            self.hideInputsIframe();
          }
        },
          React.createElement("img", { 
            src: "//wwwcdn.cincopa.com/_cms/design18/images/close.png"
          },)
          ),
      ),
      React.createElement("div", {
        className: "cp-insert-from-cincopa-cnt insert-short-code-disabled-asset"
      },
        React.createElement("p", {
          className: ""
        }, 'Media RID'),
        React.createElement("input", {
          className: "cp-import-from-cincopa-input",
          type: "text",
          onChange: function (e) {
            self.changeAssetRIDInput(e.target.value);
          },
          value: inputValAsset, 
        }),
      ),
      React.createElement("a", {
        className: "cp-connect-link",
        href: cp_site_url+"/wp-admin/options-general.php?page=cincopaoptions",
        style: {"display": isToken ? 'none' : 'flex'}
      },"Connect"),
      React.createElement("div", {
        className: "cp-library-editor-incorrect-rid",
        style: {
          "display": !self.state.showIncorrectRid ? "none" : "block"
        }
      }, "Incorrect rid"),
      React.createElement("div", {
        className: "cp-insert-from-cincopa-cnt",
        style: {"position": "relative", "top": "-12px"}
      },
        React.createElement("p", {
          className: "",
        }, 'Gallery RID'),
        React.createElement("input", {
          className: "cp-import-from-cincopa-input",
          type: "text",
          onChange: function (e) {
            self.changeGalleryFIDInput(e.target.value);
          },
          value: inputValGallery,
        }),
      ),
      React.createElement("div", {
        className: "cp_button-block"
      },
        React.createElement("a", {
          className: 'cp_button cp-import-asset',
          onClick: function () {
            var idToRender = self.state.rid;
            if (self.state.fid) {
              self.props.updatePreview({
                fid: self.state.fid,
                action: 'insertedGallery'
              });
            } else if (idToRender) {
              var url = `https://api.cincopa.com/v2/asset.list.json?api_token=${cp_token}&rid=${idToRender}`;
              fetch(url)
                .then((response) => {
                  return response.json();
                }).then((data) => {
                  if (data && data["items"] && data["items"][0]) {
                    var newData = {
                      item: data["items"][0]
                    };
                    newData.defaults = cincopa_defaults;
                    newData.action = 'insertedItem';
                    self.props.updatePreview(newData);
                  } else {
                    self.showIncorrectRid();
                    self.props.updatePreview();
                  }
                });
            } else {
              return
            }
          }
        }, "Embed"),
      )
      
      ),


    )

  }
}

self.render = function () {
  return React.createElement("div", {
      className: "library-editor-block"
    },
    (typeof self.props.wpprops.attributes.content == 'undefined') && React.createElement("div", {
        className: '',

      },
      React.createElement("div", {}, 'Select file from your cincopa account.'),
      React.createElement("a", {
        className: 'cp_button',
        onClick: self.openLibraryEditor
      }, "Insert from Cincopa")
    ),
    React.createElement("iframe", {
      src: 'https://www.cincopa.com/media-platform/api/library-editor.aspx?disable_editor=y&api_token=' + cp_token,
      className: "library-editor-block__iframe",
      allow: "microphone *; camera *; display-capture *",
      style: {
        'display': !self.state.show ? 'none' : 'flex'
      }
    })
  )

}

CincopaEmbed.prototype = Object.create(React.Component.prototype);

function CincopaEmbed(props) {
  React.Component.constructor.call(this);
  var self = this;

  self.state = {
    embedId: '',
    isLoggedin: false,
    isStatusChecked: false,
    savedCode: props.savedCode ? props.savedCode : '',
    isClick: false,
    defaults: {},
    editorBlock: false,
    showPreview:false
  };
  
  var userStatusTimer;
  self.checkUserStatusAjax = function () {
    checkLoginStatus({
      success: function () {
        self.setState({
          isStatusChecked: true
        })
        
        if (cp_user_status) {
          clearInterval(userStatusTimer)
          self.setState({
            isLoggedin: true
          });
          isToken = true;
        } else {
          isToken = false;
        }
      },
      error: function () {
        isToken = false;
        clearInterval(userStatusTimer)
        self.setState({
          isStatusChecked: true
        });
      }
    })
  }
  self.checkUserStatusAjax();
  userStatusTimer = setInterval(function () {
    self.checkUserStatusAjax();
  }, 2000);

  self.updatePreview = function (data) {
    var embedType = data.action == 'insertedGallery' ? 'gallery' : data.item.type;

    var newData = {
      embedId: data.fid || data.item.rid,
      embedType: embedType,
      isClick: true,
      defaults: data.defaults,
      showPreview:true
    }

    self.setState(prev => ({...prev, ...newData}));


    /* call props to allow wp save */
    if (embedType != 'gallery') {
      self.props.onCreateEmbed(data.defaults[data.item.type] + '!' + data.item.rid, false);

    } else {
      self.props.onCreateEmbed(data.fid, false);
    }
  }

  self.componentDidUpdate = function (prevProps) {
  }

  self.setPreviewVisibility = function(visibility){
    self.setState(prev => ({...prev,showPreview:visibility}))
  }

  self.render = function () {
    if (self.state.isStatusChecked) {
        return React.createElement(
          "div", {
            className: 'cp_embed-wrapper ' + (self.state.embedId || self.state.savedCode ? 'cp_embed-wrapper--view' : '')
          },
          (!props.savedCode && !self.state.showPreview || self.props.wpprops.attributes.update ) && React.createElement(LibraryEditor, {
            updatePreview: self.updatePreview,
            wpprops: self.props.wpprops,
            setPreviewVisibility: self.setPreviewVisibility,
            isLoggedin: self.state.isLoggedin
          }),
          ( (props.savedCode || self.state.showPreview ) && !self.props.wpprops.attributes.update) && React.createElement(Preview, {
            embedId: self.state.embedId,
            embedType: self.state.embedType,
            defaults: self.state.defaults,
            savedCode: self.state.savedCode
          }),
        );
    } else {
      return React.createElement(
        "div", {
          className: 'cp_loader'
        }, 'loading...'
      );
    }


  }
}

//var isLoggedin = self.state.isLoggedin;
function mcm_register_menu_card_section_block() {
  var BlockControls =  wp.blockEditor.BlockControls;
  var el = wp.element.createElement;
  var InspectorControls = wp.editor.InspectorControls;
  var PanelBody = wp.components.PanelBody;

  var cincopaIcon = el('img', {
      className: "cp_insert-from-cincopa-image",
      src: "//wwwcdn.cincopa.com/_cms/design18/images/cincopa_logo_circle.png"
    },
    // el('g', {
    //     transform: "translate(0.000000,256.000000) scale(0.100000,-0.100000)"
    //   },
    //   el('path', {
    //     d: 'M1059 2535 c-262 -49 -478 -164 -670 -354 -195 -195 -319 -427 -364 -685 -22 -127 -20 -350 4 -473 69 -354 261 -637 521 -768 126 -64 221 -87 371 -93 251 -9 392 39 535 182 131 132 181 256 172 428 -7 117 -44 197 -131 282 -78 76 -175 131 -361 206 -79 32 -170 75 -200 95 -85 56 -170 156 -222 260 -57 113 -72 195 -57 295 42 272 284 459 732 567 80 19 153 38 161 40 74 26 -366 42 -491 18z'
    //   }),
    //   el('path', {
    //     d: 'M1390 2231 c-181 -40 -322 -110 -421 -211 -114 -115 -150 -231 -94 -298 38 -45 90 -66 337 -136 239 -68 341 -106 433 -161 217 -131 328 -336 312 -579 -8 -123 -28 -191 -92 -321 -75 -150 -168 -278 -302 -414 l-113 -114 73 5 c127 9 332 91 492 197 94 62 270 238 335 335 67 101 141 256 177 371 27 89 28 92 28 365 0 270 0 277 -28 375 -52 189 -150 352 -262 438 -70 53 -183 105 -294 134 -84 23 -121 26 -296 29 -168 3 -214 1 -285 -15z'
    //   }, )
    // )
  );

  wp.blocks.registerBlockType('cincopa/embed', {
    title: 'Cincopa Video & Image Gallery',
    icon: cincopaIcon,
    category: 'media',
    attributes: {
      content: {
        type: 'string'
      },
      update: {
        type: 'bool'
      }
    },
    innerBlocks: [
      {
          name: 'cincopa/embed',
          attributes: {
              /* translators: example text. */
              content:'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent et eros eu felis.'
          },
      },
    ],
    /* This configures how the content and color fields will work, and sets up the necessary elements */

    edit: function (props) {

      function updateContent(id) {
        props.setAttributes({
          content: id,
          update:false
        })
      }

      
      
      
      return [props.attributes.content && el(BlockControls, {
            key: 'controls'
          }, // Display controls when the block is clicked on.
          el('div', {
              className: 'cp-components-toolbar'
            },
            el('span', {
                style: {

                },
                onClick: function () {
                  props.setAttributes({
                    update: !props.attributes.update
                  })
                }
              },
              "Edit")
          )
        ),
        el(
            InspectorControls,
            null,
            el("div",{
              className: "cp-connecting-cnt",
              style: {"display": isToken ? "none" : "block"}
            }, "",
            React.createElement("p", {

            },"Connect Cincopa to Wordpress to import your media files and galleries"),
            React.createElement("a", {
              className: 'cp_button cp_button-connect',
              href: cp_site_url+"/wp-admin/options-general.php?page=cincopaoptions",             
              initialOpen: true
            },"Connect"))
        ),
        React.createElement(CincopaEmbed, {
          onCreateEmbed: updateContent,
          savedCode: props.attributes.content,
          wpprops: props
        })
      ]
    },
    save: function (props) {
      if (props.attributes.content) {
        that.assetRid = "";
        that.galleryFid = "";
        return '[cincopa ' + props.attributes.content + ']';
      } else {
        return '';
      }

    }
  })

}
wp.domReady(mcm_register_menu_card_section_block);