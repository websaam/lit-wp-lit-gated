<?php

/** 
 * Require the core editor class so we can call wp_link_dialog function to print the HTML.
 * Luckly it is public static method ;)
 */
wp_enqueue_script('wplink');
wp_enqueue_style( 'editor-buttons' );
require_once ABSPATH . "wp-includes/class-wp-editor.php";
_WP_Editors::wp_link_dialog(); 

/**
 * Get row HTML
 * @param { String } $anchor/$path
 * @return { String } HTML
 */
function get_link_row($anchor = ''){
    return '<div class="lit-btn-select-link-row">
                <div class="lit-btn-select-link lit-btn-secondary">Select Link</div>
                <textarea class="lit-selected-link">'.$anchor.'</textarea>
            </div>';
}

/**
 * Get "Select Link" section
 * @param { String } $paths : the resource paths you want to sign
 * @return { String } HTML
 */
function get_links_section($paths){
    
    $link_content = '';

    // -- basically if array is 0
    if( $paths == null){
        $link_content = get_link_row();
        return $link_content;
    }

    // -- else loop through array
    foreach($paths as $path){
        $link_content .= get_link_row($path->anchor);
    }

    $link_content .= get_link_row();

    return $link_content;
}

/**
 * Get the row snippet
 * @param { String } $created_at : timestamp that this data was created at
 * @param { String } $accs : access control conditions in a readable format
 * @param { String } $paths : the resource paths you want to sign
 * @param { Boolean } $signed : if this row a signed resource
 * @param { Boolean } withoutWrapper : Including the outer div or not
 * @return { String } HTML 
 */
function get_snippet($created_at, $accs = '', $paths = '', $signed = false, $withoutWrapper = false){

    $signed_class = $signed ? 'signed' : '';
    $wrapper_start = $withoutWrapper ? '' : '<div data-created-at="'.$created_at.'" class="lit-table-row '.$signed_class.'">';
    $wrapper_end = $withoutWrapper ? '' : '</div>';

    return $wrapper_start . '

        <!-- Progress bar -->
        <div class="lit-progress-bar"></div>

        <!-- Lit Signed -->
        <div class="lit-signed">
            <section>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <span>Click to edit</span>
            </section>
        </div>

        <!-- Row Panel -->
        <div class="lit-table-row__panel">
            <section class="lit-btn-close">
                <div class="lit-table-row__close"></div>
            </section>
            <section class="lit-btn-sign">
                <div class="lit-btn-sign__text">Sign</div>
            </section>
        </div>

        <!-- Row #id --> 
        <div class="lit-table-col lit-table-col__id">
            <span>1</span>
        </div>

        <!-- Row Accs -->
        <div class="lit-table-col">
            <div class="lit-humanised">Not ready yet...</div>
            <textarea class="input-accs" rows="4">'.$accs.'</textarea>
            <div class="lit-btn-create-requirement lit-btn-secondary">Create Requirement</div>
        </div>

        <!-- Row Select Link -->
        <div class="lit-table-col lit-table-col__path">
            <div class="lit-select-links-container">
                '. get_links_section($paths) .' 
            </div>
        </div>
        
    ' . $wrapper_end;
}
;?>

<!-- Menu page wrapper -->
<div class="wrap">

    <!-- Access Control Modal -->
    <div id="shareModal"></div>

    <!-- logo -->
    <div class="lit-logo">
        <section>
            <img src="<?php echo LIT_LOGO ;?>"/>
            <h1>Lit Protocol Settings</h1>
        </section>
    </div><!-- ...logo -->

    <!-- form -->
    <form id="lit-form" method="POST" action="options.php">

        <!-- ===== REQUIRED PHP SETTINGS FOR THE ADMIN MENU -->
        <?php
            settings_fields( LIT_MENU_GROUP ); // settings group name
            do_settings_sections( LIT_MENU_SLUG ); // just a page slug
            $settings = json_decode(base64_decode(get_option( 'lit-settings' )));
        ;?>
        <!-- ===== ...REQUIRED PHP SETTINGS FOR THE ADMIN MENU -->

        <!-- table -->
        <div class="lit-table">

            <!-- left -->
            <div class="lit-table-left">
                <div id="lit-table-body">
                    
                    <!-- header -->
                    <div class="lit-table-row lit-table-header">
                        <div class="lit-table-col">#</div>
                        <div class="lit-table-col">Access Control Conditions</div>
                        <div class="lit-table-col">Path</div>
                    </div><!-- ...header -->

                    <!-- ===== PHP FOR LOOP CONTENT BEGINS ===== -->
                    <?php
                    // added (array) to cast $settings as an array for null assertion error in PHP8 and Wordpress 5.9+                     
                    if(count((array)$settings) > 0) {

                        echo '<div class="lit-debug">';

                        // ----- (DEBUG) DELETE THIS AFTER
                        // echo '<br>============ Data ============<br>';
                        // echo '<pre><code style="font-size:10px;">';
                        // print_r($settings);
                        // echo '</code></pre>';
                        // ----- ...(DEBUG) DELETE THIS AFTER
                        
                        echo '</div>';

                        foreach($settings as $i=>$setting){
                            echo get_snippet($setting->created_at, $setting->accs, $setting->paths, $setting->signed);
                        }
                    }
                    ;?>
                    <!-- ===== ...PHP FOR LOOP CONTENT ENDS ===== -->
                </div>

                <!-- control -->
                <div class="lit-controls">
                    <div id="btn-lit-add-row" class="lit-btn-primary">Add Row</div>
                </div><!-- ...control -->
            </div><!-- ... left -->
            
            <!-- right -->
            <div class="lit-table-right">
                <input type="submit" id="btn-lit-submit" class="lit-btn-primary" value="Save Changes">

                <div class="lit-menu-controls">
                    <input type="checkbox" name="lit-toggle-signed" id="lit-toggle-signed" />
                    <label for="lit-toggle-signed">Hide Signed Resources</label>
                </div>

            </div><!-- ...right -->

        </div><!-- ...table -->
    </form><!-- ...form -->
</div><!-- ...menu page wrapper -->

<!-- 
// ================================================================================
// +                                   Javascript                                 +
// ================================================================================ 
-->
<script>

// -------------------- Utils -------------------- //
//
// A modified forEach function but async
// @param { Array } array
// @param { Function } callback
// @return { void } 
//
async function asyncForEach(array, callback) {
    for (let index = 0; index < array.length; index++) {
        await callback(array[index], index, array);
    }
}

//
// Remove trailing slash
// @param { String } str 
// @returns { String } str without trailing /
//
const removeTrailingSlash = (str) => {
    return (str.endsWith('/') ? str.slice(0, -1) : str);
}

//
// Get current timestamp
// @returns { Int } timestamp
//
const getTimestamp = () => new Date().getTime();

// -------------------- Access Control Conditions Modal -------------------- //

// 
// Unmount the modal from the page
// @return { void }
//
const closeModal = () => {
    var div = document.getElementById("shareModal");
    div.style.zIndex = 0;
    ACCM.ReactContentRenderer.unmount(div)
};

// 
// Mount the modal on the page
// @param { Function }
// @return { void }
//
const openShareModal = (callback) => {

    var div = document.getElementById("shareModal");
    div.style.zIndex = 2;

    ACCM.ReactContentRenderer.render(
        ACCM.ShareModal,
        {
            sharingItems: [],
            onUnifiedAccessControlConditionsSelected: callback,
            onClose: closeModal,
            getSharingLink: (sharingItem) => {
                console.log("getSharingLink", sharingItem);
                return "";
            },
            showStep: "ableToAccess",
        },
        // -- target DOM node
        document.getElementById("shareModal")
    );
}

// -------------------- Data Preparation -------------------- //

//
// Check if String is <a> tag or just the href src
// @param { String } str
// @return { Boolean } 
//
const isAnchor = (str) => {
    const href_regex = /href="([^\'\"]+)/g;
    return href_regex.exec(str) != null;
}

//
// Get href inside <a> tag or just the href src
// @param { String } str
// @return { String } href
//
const getHref = (str) => isAnchor(str) ? str.split('"')[1] : str;

//
// Split URL into parts as resources
// @param { String } url
// @return { base_url, path }
//
const getURLParts = (url) => {
    const data = url.split('/');
    const base_url = data[2];
    const splitter = url.split('/')[3];
    const path = '/' + removeTrailingSlash(splitter + url.split(splitter)[1]);

    return { base_url, path };
}

//
// Compress all fields into a single string
// @return { String } 
//
const compressedData = () => {
    var inputs = document.getElementsByClassName('input-accs');
    var paths = document.getElementsByClassName('lit-selected-link');
    var rows = [...document.getElementsByClassName('lit-table-row')].filter((e) => !e.classList.contains('lit-table-header'));
    var containers = document.getElementsByClassName('lit-select-links-container');

    console.log("Containers:", containers);

    var bucket = [];

    // for each row
    [...containers].forEach((_, i) => {

        console.log(`(${i}) -----`);

        var new_rows = containers[i].querySelectorAll('.lit-btn-select-link-row');

        const _paths = [];

        [...new_rows].forEach((_path) => {
            var _value = _path.querySelector('.lit-btn-secondary').innerText;

            if(_value != 'Select Link'){

                const { base_url, path } = getURLParts(_value);

                _paths.push({
                    anchor: _value,
                    base_url,
                    path,
                });
            }
        });

        // console.log("PATHS:", _paths);

        const input = inputs[i];
        const anchor_tag = paths[i];
        const current_row = rows[i];
        
        // const href = getHref(anchor_tag.value);
        // const { base_url, path } = getURLParts(href);
        const signed = current_row.classList.contains('signed');
        const created_at = current_row.getAttribute('data-created-at');
        
        console.log("🔥 paths:", _paths);
        // console.log("🔥 anchor:", href);
        // console.log("🔥 base_url:", base_url);
        // console.log("🔥 path:", path);
        console.log("🔥 signed:", signed);
        console.log("🔥 created_at:", created_at);

        var obj = {};
        obj.accs = input.value;
        obj.paths = _paths;
        // obj.base_url = base_url;
        // obj.path = path;
        // obj.anchor = href;
        obj.signed = signed;
        obj.created_at = created_at;

        bucket.push(obj);
    }); 
    return btoa(JSON.stringify(bucket));
}

// -------------------- Handler -------------------- //
//
// Handle deleting row
// @return { void } 
//
const handleRowDeletion = () =>{
    console.log("Handle Row Deletion");
    const btnsDelete = document.getElementsByClassName('lit-table-row__close');

    const handleClick = (e) => {
        var targetRow = e.target.parentElement.parentElement.parentElement;
        targetRow.classList.add('deleting');
        setTimeout(() => {
            targetRow.remove();
        }, 300);
    }

    [...btnsDelete].forEach((btn) => {
        btn.addEventListener('click', handleClick);
    });
}

//
// Handle "add row" button
// @param { Function } callback | a list of states/actions you want to update 
// after a new row is added.
// @return { void }
//
const handleBtnAddRow = (callback) => {
    const btnNewRow = document.getElementById('btn-lit-add-row');
    const tableBody = document.getElementById('lit-table-body');
    const nextIndex = document.getElementsByClassName('lit-table-row');

    btnNewRow.addEventListener('click', (e) => {
        console.log("Added new row");
        const template = Object.assign(document.createElement('div'), {
            classList: 'lit-table-row',
            innerHTML: `<?php echo get_snippet('', '','','', true) ;?>`,
        });
        const timestamp = getTimestamp();
        template.setAttribute("data-created-at", timestamp);
        tableBody.appendChild(template);
        
        // list of actions
        callback();

    });
}

//
// Handle form submission
// @return { void } 
//
const handleSubmit = () => {
    const btnSubmit = document.getElementById('btn-lit-submit');
    const mainField = document.getElementById('lit-settings');
    const form = document.getElementById('lit-form');

    btnSubmit.addEventListener('click', (e) => {
        e.preventDefault();
        const data = compressedData();
        mainField.value = data;
        console.log("Data:", data);

        form.submit();
    });
}

//
// Handle create requirement buttons
// @return { void } 
//
const handleBtnsCreateRequirements = () => {
    const btns = document.getElementsByClassName('lit-btn-create-requirement');

    const handleClick = (e) => {

        const textArea = e.target.previousElementSibling;
        
        openShareModal((accessControlConditions) => {

            const accs = accessControlConditions.unifiedAccessControlConditions;
            console.warn("accessControlConditions:", accs);

            closeModal();
            textArea.value = JSON.stringify(accs);
            handleHumanised();
        });
    }

    const handleListener = (btn) => btn.addEventListener('click', handleClick);

    [...btns].forEach(handleListener);    
}

//
// Handle humanised access control conditions
// @return { void } 
//
const handleHumanised = () => {
    const fields = document.getElementsByClassName('lit-humanised');

    asyncForEach([...fields], async (field) => {
        console.log("field.nextElementSibling:", field.nextElementSibling);

        const conditionObject = JSON.parse(field.nextElementSibling.value) ?? JSON.parse(field.nextElementSibling.accessControlConditions).accessControlConditions;

        console.log("conditionObject:", conditionObject);

        const readable = await LitJsSdk.humanizeAccessControlConditions({ unifiedAccessControlConditions: conditionObject});
        
        console.log("readable:", readable)
        field.innerText = readable;
    });
}

//
// Handle Select Link buttons
// @return { void }
//
const handleBtnsSelectLink = () => {
    
    const btns = document.getElementsByClassName('lit-btn-select-link');

    const onSubmit = (bool) => handleBtnsSelectLinkInnerText({addSelectLinkButton: bool});

    const handleClick = (e) => {

        console.warn("..handleBtnsSelectLink");

        const isSelectLink = e.target.innerText == 'Select Link';

        // const targetRow = e.target.parentElement.parentElement;
        const textarea = e.target.nextElementSibling;
        textarea.setAttribute('id', 'js-temp-insert');

        // console.log("targetRow:", targetRow);
        console.log("textarea:", textarea);
        
        // clear textarea
        textarea.value = '';
        
        const textarea_id = textarea.getAttribute('id');
        wpLink.open(textarea_id);

        var btnSubmit = document.getElementById('wp-link-submit');

        btnSubmit.addEventListener('click', () => onSubmit(isSelectLink));

        setTimeout(() => {
            highlightSignedRowsOnSearchList();
        }, 500);
    }

    const handleListener = (btn) => btn.addEventListener('click', handleClick);

    [...btns].forEach(handleListener);
}

//
// Create and return `Select Link` button
// @return { HTMLElement }
//
const createSelectLinkButton = () => {

    const row = Object.assign(document.createElement('div'), {
        classList: 'lit-btn-select-link-row',
    });

    const btnSelectLink = Object.assign(document.createElement('div'), {
        classList: 'lit-btn-select-link lit-btn-secondary',
        innerText: 'Select Link',
    });

    const textArea = Object.assign(document.createElement('textarea'), {
        classList: 'lit-selected-link',
    });

    row.appendChild(btnSelectLink)
    row.appendChild(textArea)

    setTimeout(() => {
        handleBtnsSelectLink();
    }, 500)

    return row;
}

//
// Handle Selected Link Text
// @param { Object } options
// @param { Boolean } options.addSelectLinkButton
// @return { void } 
//
const handleBtnsSelectLinkInnerText = (options) => {
    
    console.warn("...handleBtnsSelectLinkInnerText");

    handleSelectLinkStyle();

    [...document.getElementsByClassName('lit-selected-link')].forEach((textarea, i) => {

        console.log(`${i}: textarea:`, textarea);
        
        if( textarea.value.length > 1){
            const btn = textarea.previousElementSibling;
            btn.innerHTML = getHref(textarea.value);
        }

        // -- This add a new "Select Link" button
        if(options?.addSelectLinkButton && textarea?.getAttribute('id') != null){

            // -- remove 'js-temp-insert' id
            textarea?.removeAttribute('id');

            // -- add new 'Select Link' button
            let container = textarea.parentElement.parentElement;
            console.log("Container:", container);

            const row = createSelectLinkButton();
    
            container.appendChild(row)
        }

    });
}


//
// Handle when access control conditions change
// @return { void }
//
const handleOnAccsInputChange = () => {
    [...document.getElementsByClassName('input-accs')].forEach((input) => {
        input.addEventListener('input', (e) => {
            handleHumanised();
        });
    });
}

//
// Handle rows index and update automatically when adding new row
// @returns { void } 
//
const handleRowsIndex = () => {
    const rows = document.getElementsByClassName('lit-table-row');
    
    [...rows].forEach((row, i) => {

        // -- validate if it's NOT header
        if(row.classList.contains('lit-table-header')) return;
        
        // -- prepare
        let id = row.querySelector('.lit-table-col__id > span');
        // let selected_link = row.querySelector('.lit-selected-link');
        
        // -- excute
        id.innerText = i;
        // selected_link.setAttribute('id', 'lit-selected-link-' + i);
    });
}

//
// Handle sign buttons
// @return { void } 
//
const handleBtnsSign = () => {

    var btns = document.getElementsByClassName('lit-btn-sign');

    const handleClick = async (e) => {

        console.warn("[handleBtnsSign]");

        const target_row = e.target.parentElement.parentElement;
        const progress_bar = target_row.querySelector('.lit-progress-bar');
        const accs_textarea = target_row.querySelector('.input-accs');
        const link_textarea = target_row.querySelector('.lit-selected-link');
        const accs = target_row.querySelector('.lit-humanised').innerText;
        // const url = target_row.querySelector('.lit-btn-select-link').innerText;
        let rows = target_row.querySelectorAll('.lit-btn-select-link-row');
        rows = [...rows].filter((row) => row.innerText != 'Select Link');

        console.log("[handleBtnsSign] target_row:", target_row);
        console.log("[handleBtnsSign] accs_textarea:", accs_textarea);
        console.log("[handleBtnsSign] link_textarea:", link_textarea);
        console.log("[handleBtnsSign] accs:", accs);
        // console.log("url:", url);

        console.log("[handleBtnsSign] rows:", rows);

        const LIT_EVM_CHAINS = ["ethereum","polygon","fantom","xdai","bsc","arbitrum","avalanche","fuji","harmony","kovan","mumbai","goerli","ropsten","rinkeby","cronos","optimism","celo","aurora","eluvio","alfajores","xdc","evmos","evmosTestnet"];
        const LIT_SVM_CHAINS = ["solana","solanaDevnet","solanaTestnet"];
        const LIT_COSMOS_CHAINS = ["cosmos","kyve","evmosCosmos","evmosCosmosTestnet"];

        let sign;
        let jwt;
        let args;

        await asyncForEach(rows, async (row, i) => {
            const url = row.querySelector('.lit-btn-secondary').innerText;

            console.warn(`[handleBtnsSign] ====== SIGNING: ${i}: url:`, url, " ======");
    
            // -- validate
            if(accs_textarea.value == null || accs_textarea.value == '' || accs_textarea.value == undefined || accs_textarea.value.length < 5){
                console.log("[handleBtnsSign] Error");
                accs_textarea.classList.add('error');
                setTimeout(() => accs_textarea.classList.remove('error'), 2000);
                return;
            }
    
            if(link_textarea.value == null || link_textarea.value == '' || link_textarea.value == undefined || link_textarea.value.length < 5){
                console.log("[handleBtnsSign] Error");
                link_textarea.previousElementSibling.classList.add('error');
                setTimeout(() => link_textarea.previousElementSibling.classList.remove('error'), 2000);
                return;
            }
            
            // -- prompt message
            // const message = `WARNING! Are you sure you want to sign this resource?\nOnce it's signed it cannot be changed or deleted forever.\n\n${accs}\n${url}\n\n \n`;
            // const confirmed = confirm(message) == true ? true : false;
            // if(! confirmed ) return;
            
            // -- prepare resource id
            let ethAuthSig;
            let solAuthSig;

            const conditionsObject = JSON.parse(accs_textarea.value);
    
            const accessControlConditions = conditionsObject.accessControlConditions ?? conditionsObject;

            conditionsObject.permanent = false;

            const chainsToBeSigned = accessControlConditions.map(cond => cond.chain).filter(cond => !!cond);
            console.log("[handleBtnsSign] chainsToBeSigned:", chainsToBeSigned);

            const evmChains = chainsToBeSigned.filter(chain => LIT_EVM_CHAINS.includes(chain));
            const isEVM = evmChains.length > 0;

            const svmChains = chainsToBeSigned.filter(chain => LIT_SVM_CHAINS.includes(chain));
            const isSVM = svmChains.length > 0;

            console.log("[handleBtnsSign] evmChains:", evmChains);
            console.log("[handleBtnsSign] svmChains:", svmChains);
            console.log("[handleBtnsSign] isEVM:", isEVM);
            console.log("[handleBtnsSign] isSVM:", isSVM);

            if( isEVM ){
                try {
                    ethAuthSig = await LitJsSdk.checkAndSignAuthMessage({chain: evmChains[0]});
                    console.log("[handleBtnsSign] ethAuthSig:", ethAuthSig);
                } catch (error) {
                    console.log("[handleBtnsSign] isEVM Error:", error);
                    if (error.errorCode === "no_wallet") {
                        alert("Please install an Ethereum wallet to use this feature.  You can do this by installing MetaMask from https://metamask.io/");
                    } else {
                        alert("An unknown error occurred when trying to get a signature from your wallet.  You can find it in the console.  Please email support@litprotocol.com with a bug report");
                    }
                    return;
                }
            }

            if( isSVM ){
                try{
                    solAuthSig = await LitJsSdk.checkAndSignAuthMessage({chain: svmChains[0]});
                    console.log("[handleBtnsSign] solAuthSig:", solAuthSig);
                }catch (error) {
                    console.log("[handleBtnsSign] isSVM Error:", error);
                    if (error.errorCode === "no_wallet") {
                        alert("Please install an Solana wallet to use this feature.  You can do this by installing Phantom from https://phantom.app/download/");
                    } else {
                        alert("An unknown error occurred when trying to get a signature from your wallet.  You can find it in the console.  Please email support@litprotocol.com with a bug report");
                    }
                    return;
                }
            }

            console.log("[handleBtnsSign] ethAuthSig:", ethAuthSig);
            console.log("[handleBtnsSign] solAuthSig:", solAuthSig);

            // const href = getHref(link_textarea.value);
            const { base_url, path } = getURLParts(url);
            
            const timestamp = target_row.getAttribute('data-created-at');
    
            const resourceId = {
                baseUrl: base_url,
                path,
                orgId: "",
                role: "",
                extraData: timestamp,
            };
    
            console.log("[handleBtnsSign] RESOURCE_ID:", resourceId);
            console.log("[handleBtnsSign] accessControlConditions:", accessControlConditions);
    
            // -- start signing
            console.log("[handleBtnsSign] Signing...");

            const litNodeClient = new LitJsSdk.LitNodeClient();
            await litNodeClient.connect();

            try{

                // -- EVM CHAIN ONLY
                if( isEVM && !isSVM ){
                    args = {
                        accessControlConditions,
                        chain: evmChains[0],
                        authSig: ethAuthSig,
                        resourceId,
                        permanent: false,
                    };
                }

                // -- SVM CHAIN ONLY
                if( isSVM && !isEVM ){
                    args = {
                        solRpcConditions: accessControlConditions,
                        chain: svmChains[0],
                        authSig: solAuthSig,
                        resourceId,
                        permanent: false,
                    };
                }

                // -- BOTH CHAINS
                if( isSVM && isEVM ){
                    console.log("[handleBtnsSign] Both EVM & SVM Chains");
                    args = { 
                        unifiedAccessControlConditions: accessControlConditions,
                        authSig: {
                            solana: solAuthSig,
                            ethereum: ethAuthSig,
                            // ...isSVM && {solana: solAuthSig},
                            // ...isEVM && {ethereum: ethAuthSig},
                        },
                        resourceId,
                        permanent: false,
                    };
                }

                console.log("[handleBtnsSign] args:", args);

                sign = await litNodeClient.saveSigningCondition(args);
                
            }catch(e){
                console.error("[handleBtnsSign] Something went wrong when signing this resource.", e);
                alert(`[${e.name}][${e.errorCode}] ${e.message}`);
                return;
            }

            console.log("[handleBtnsSign] sign:", sign);
            
            // [Lit-JS-SDK] most common error: {"errorCode":"incorrect_access_control_conditions","message":"The access control conditions you passed in do not match the ones that were set by the condition creator for this resourceId."}
            try{
                jwt = await litNodeClient.getSignedToken(args);
                console.log("[handleBtnsSign] jwt:", jwt);
            }catch(e){
                console.error("[handleBtnsSign] Unable to get JWT.", e);
                alert(`[${e.name}][${e.errorCode}] ${e.message}`);
                return;
            }

            row.classList.add('locked');
            progress_bar.style.width = ((i+1) / rows.length) * 100 + '%';    
        });

        if ( ! jwt || ! sign ) return;

        await new Promise(r => setTimeout(r, 1000));

        target_row.classList.add('signed');
        const btnSubmit = document.getElementById('btn-lit-submit');
        btnSubmit.click();

    }
    
    const handleListener = (btn) => btn.addEventListener('click', handleClick);

    [...btns].forEach(handleListener);

}

//
// Handle hide/show rows that are signed/unsigned
// @return { void }
//
const handleRowsToggle = () => {

    // -- prepare
    const rows = document.getElementsByClassName('signed');
    const btn = document.getElementById('lit-toggle-signed');
    
    // -- prepare re-usable methods
    const hideRows = () => {
        localStorage['hide-signed-rows'] = true;
        [...rows].forEach((row) => {
            row.classList.add('hide');
        });
    }
    const showRows = () => {
        localStorage['hide-signed-rows'] = false;
        [...rows].forEach((row) => {
            row.classList.remove('hide');
        });
    }

    // check local stroage settings
    if(localStorage['hide-signed-rows'] == 'true'){
        hideRows();
        btn.checked = true;
    }else{
        showRows();
        btn.checked = false;
    }

    //  when the button is clicked
    btn.addEventListener('click', (e) => {

        if(btn.checked){
            hideRows();
        }else{
            showRows();
        }
    });
}

//
// Handle highlight signed rows on search list
// @returns { void }
//
const highlightSignedRowsOnSearchList = () => {

    console.warn("highlightSignedRowsOnSearchList:", highlightSignedRowsOnSearchList)

    var search_results = [...document.getElementById('most-recent-results').querySelectorAll('input')];
    var signed_results = [...document.getElementsByClassName('signed')].map((e) => e.querySelector('.lit-selected-link').innerText);

    console.log("search_results:", search_results);
    console.log("signed_results:", signed_results);

    search_results.forEach((result) => {
        if(signed_results.includes(result.value)){
            // do your thing for the row
            result.parentElement.style.opacity = '0.4';
            result.parentElement.style.pointerEvents = 'none';
        }
    });
}

//
// Handle edit buttons
//
const handleEdits = () => {
    const overlays = document.getElementsByClassName('lit-signed');

    const handleClick = (e) => {
        const row = e.target.parentElement;
        e.target.classList.add('closed');
        row.setAttribute('data-created-at', getTimestamp());

        // wait 0.3s to finish fading out animation
        setTimeout(() => {
            row.classList.remove('signed');
            // submit data
            const mainField = document.getElementById('lit-settings');
            const form = document.getElementById('lit-form');
            const data = compressedData();
            mainField.value = data;
            console.log("Data:", data);
            form.submit();
        }, 300);

    }

    [...overlays].forEach((overlay) => {
        overlay.addEventListener('click', handleClick);
    });
}

// 
// Get 'Delete' button
// @return { HTMLElement } e
//
const createDeleteButton = () => {
    const btn = Object.assign(document.createElement('div'), {
        classList: 'js-select-link__delete',
        innerHTML: '<svg fill="#2c1272" xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 16 16" width="16px" height="16px"><path d="M 6.496094 1 C 5.675781 1 5 1.675781 5 2.496094 L 5 3 L 2 3 L 2 4 L 3 4 L 3 12.5 C 3 13.328125 3.671875 14 4.5 14 L 10.5 14 C 11.328125 14 12 13.328125 12 12.5 L 12 4 L 13 4 L 13 3 L 10 3 L 10 2.496094 C 10 1.675781 9.324219 1 8.503906 1 Z M 6.496094 2 L 8.503906 2 C 8.785156 2 9 2.214844 9 2.496094 L 9 3 L 6 3 L 6 2.496094 C 6 2.214844 6.214844 2 6.496094 2 Z M 5 5 L 6 5 L 6 12 L 5 12 Z M 7 5 L 8 5 L 8 12 L 7 12 Z M 9 5 L 10 5 L 10 12 L 9 12 Z"/></svg>',
    });

    // -- add event handler
    btn.addEventListener('click', (e) => {
        console.log(e.target.parentElement.remove());
    });

    return btn;
}

// 
// Handle 'Select Link' Style
// 
const handleSelectLinkStyle = () =>{

    console.warn("handleSelectLinkStyle");

    setTimeout(() => {
        [...document.querySelectorAll('.lit-select-links-container .lit-btn-secondary')].forEach((item) => {
            
            if(item.innerText == 'Select Link'){
                item.classList.add('js-select-link');
            }else{
                item.classList.remove('js-select-link');

                // -- if delete button doesn't exist already
                if( item.parentElement.querySelector('.js-select-link__delete') == null){
                    const btnDelete = createDeleteButton();
                    item.parentElement.appendChild(btnDelete);
                }

            }
        
        });
    }, 50)

}


// -------------------- Entry Point -------------------- //
(() => {
    console.log("🔥 ===================== Lit-Gated Admin Panel =====================🔥 ");
    
    LitJsSdk.litJsSdkLoadedInALIT();
    
    // -- list of handlers
    handleSubmit();
    handleBtnAddRow(() => {
        handleRowDeletion();
        handleBtnsCreateRequirements();
        handleRowsIndex();
        handleBtnsSelectLink();
        handleBtnsSelectLinkInnerText();
        handleOnAccsInputChange();
        handleBtnsSign();
        // handleSelectLinkStyle();
    });
    handleRowDeletion();
    handleBtnsCreateRequirements();
    handleHumanised();
    handleBtnsSelectLink();
    handleOnAccsInputChange();
    handleRowsIndex();
    handleBtnsSelectLinkInnerText();
    handleBtnsSign();
    handleRowsToggle();
    handleEdits();
    // handleSelectLinkStyle();

})();

</script>