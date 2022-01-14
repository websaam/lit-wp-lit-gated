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
 * Get the row snippet
 * @param { String } $accs : access control conditions in a readable format
 * @param { String } $path : the resource path you want to sign
 * @param { Boolean } $signed : if this row a signed resource
 * @param { Boolean } withoutWrapper : Including the outer div or not
 * @return { String } HTML 
 */
function get_snippet($accs = '', $path = '', $signed = false, $withoutWrapper = false){

    $signed_class = $signed ? 'signed' : '';
    $wrapper_start = $withoutWrapper ? '' : '<div class="lit-table-row '.$signed_class.'">';
    $wrapper_end = $withoutWrapper ? '' : '</div>';

    return $wrapper_start . '

        <!-- Lit Signed -->
        <div class="lit-signed">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
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
            <div class="lit-btn-select-link lit-btn-secondary">Select Link</div>
            <textarea class="lit-selected-link">'.$path.'</textarea>
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
            <img src="https://litprotocol.com/lit-logo.png"/>
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
                    if(count($settings) > 0){
                        foreach($settings as $i=>$setting){
                            echo get_snippet($setting->accs, $setting->anchor, $setting->signed);
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

// -------------------- Access Control Conditions Modal -------------------- //

// 
// Unmount the modal from the page
// @return { void }
//
const closeModal = () => ACCM.ReactContentRenderer.unmount(document.getElementById("shareModal"));

// 
// Mount the modal on the page
// @param { Function }
// @return { void }
//
const openShareModal = (callback) => {
    ACCM.ReactContentRenderer.render(
        ACCM.ShareModal,
        {
            sharingItems: [],
            onAccessControlConditionsSelected: callback,
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

    var bucket = [];

    // for each row
    [...inputs].forEach((_, i) => {
        console.log(`(${i}) -----`);
        const input = inputs[i];
        const anchor_tag = paths[i];
        
        const href = getHref(anchor_tag.value);
        const { base_url, path } = getURLParts(href);
        const signed = rows[i].classList.contains('signed');
        
        console.log("🔥 anchor:", href);
        console.log("🔥 base_url:", base_url);
        console.log("🔥 path:", path);
        console.log("🔥 signed:", signed);

        var obj = {};
        obj.accs = input.value;
        obj.base_url = base_url;
        obj.path = path;
        obj.anchor = href;
        obj.signed = signed;

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
// Handle adding new row
// @param { Function } callback
// @return { void }
//
const handleAddNewRow = (callback) => {
    const btnNewRow = document.getElementById('btn-lit-add-row');
    const tableBody = document.getElementById('lit-table-body');
    const nextIndex = document.getElementsByClassName('lit-table-row');

    btnNewRow.addEventListener('click', (e) => {
        console.log("Added new row");
        const template = Object.assign(document.createElement('div'), {
            classList: 'lit-table-row',
            innerHTML: `<?php echo get_snippet('','','', true) ;?>`,
        });
        tableBody.appendChild(template);
        
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
const handleCreateRequirementsBtns = () => {
    const btns = document.getElementsByClassName('lit-btn-create-requirement');

    const handleClick = (e) => {

        const textArea = e.target.previousElementSibling;
        
        openShareModal((accessControlConditions) => {
            closeModal();
            textArea.value = JSON.stringify(accessControlConditions);
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
        console.log(field.nextElementSibling);
        const accessControlConditions = JSON.parse(field.nextElementSibling.value);
        const readable = await LitJsSdk.humanizeAccessControlConditions({accessControlConditions});
        field.innerText = readable;
    });
}

//
// Handle Select Link buttons
// @return { void }
//
const handleSelectLinks = () => {
    const btns = document.getElementsByClassName('lit-btn-select-link');

    const onSubmit = () => handleSelectLinksText();

    const handleClick = (e) => {
        const targetRow = e.target.parentElement.parentElement;
        const textarea = e.target.nextElementSibling;
        
        // clear textarea
        textarea.value = '';
        
        const textarea_id = textarea.getAttribute('id');;
        wpLink.open(textarea_id);

        var btnSubmit = document.getElementById('wp-link-submit');
        btnSubmit.addEventListener('click', onSubmit);
    }

    const handleListener = (btn) => btn.addEventListener('click', handleClick);

    [...btns].forEach(handleListener);
}

//
// Handle Selected Link Text
// @return { void } 
//
const handleSelectLinksText = () => {

    [...document.getElementsByClassName('lit-selected-link')].forEach((textarea) => {
        if( textarea.value.length > 1){
            const btn = textarea.previousElementSibling;
            btn.innerHTML = getHref(textarea.value);
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

        // -- validate
        if(row.classList.contains('lit-table-header')) return;
        
        // -- prepare
        let id = row.querySelector('.lit-table-col__id > span');
        let selected_link = row.querySelector('.lit-selected-link');
        
        // -- excute
        id.innerText = i;
        selected_link.setAttribute('id', 'lit-selected-link-' + i);
    });
}

//
// Handle sign buttons
// @return { void } 
//
const handleSignBtns = () => {
    var btns = document.getElementsByClassName('lit-btn-sign');

    const handleClick = async (e) => {

        const target_row = e.target.parentElement.parentElement;
        const accs_textarea = target_row.querySelector('.input-accs');
        const link_textarea = target_row.querySelector('.lit-selected-link');
        const accs = target_row.querySelector('.lit-humanised').innerText;
        const url = target_row.querySelector('.lit-btn-select-link').innerText;

        // -- validate
        if(accs_textarea.value == null || accs_textarea.value == '' || accs_textarea.value == undefined || accs_textarea.value.length < 5){
            console.log("Error");
            accs_textarea.classList.add('error');
            setTimeout(() => accs_textarea.classList.remove('error'), 2000);
            return;
        }

        if(link_textarea.value == null || link_textarea.value == '' || link_textarea.value == undefined || link_textarea.value.length < 5){
            console.log("Error");
            link_textarea.previousElementSibling.classList.add('error');
            setTimeout(() => link_textarea.previousElementSibling.classList.remove('error'), 2000);
            return;
        }
        
        // -- prompt message
        const message = `WARNING! Are you sure you want to sign this resource?\nOnce it's signed it cannot be changed or deleted forever.\n\n${accs}\n${url}\n\n \n`;
        const confirmed = confirm(message) == true ? true : false;
        if(! confirmed ) return;
        
        // -- prepare resource id
        const chain = "ethereum";
        const authSig = await LitJsSdk.checkAndSignAuthMessage({chain: chain});

        const accessControlConditions = JSON.parse(accs_textarea.value);

        const href = getHref(link_textarea.value);
        const { base_url, path } = getURLParts(href);

        const resourceId = {
            baseUrl: base_url,
            path,
            orgId: "",
            role: "",
            extraData: "",
        };

        // -- start signing
        const sign = await litNodeClient.saveSigningCondition({ 
            accessControlConditions, 
            resourceId,
            chain, 
            authSig, 
        });

        if(! sign ){
            alert("Something went wrong when signing this resource.");
            return;
        }

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
    var search_results = [...document.getElementById('most-recent-results').querySelectorAll('input')];
    var signed_results = [...document.getElementsByClassName('signed')].map((e) => e.querySelector('.lit-selected-link').innerText);

    search_results.forEach((result) => {
        if(signed_results.includes(result.value)){

            // do your thing for the row
            // result.parentElement.style.display = 'none';
        }
    });
}


// -------------------- Entry Point -------------------- //
(() => {
    console.log("🔥 ===================== Lit-Gated Admin Panel =====================🔥 ");
    
    LitJsSdk.litJsSdkLoadedInALIT();
    
    // -- list of handlers
    handleSubmit();
    handleAddNewRow(() => {
        handleRowDeletion();
        handleCreateRequirementsBtns();
        handleRowsIndex();
        handleSelectLinks();
        handleSelectLinksText();
        handleOnAccsInputChange();
        handleSignBtns();
    });
    handleRowDeletion();
    handleCreateRequirementsBtns();
    handleHumanised();
    handleSelectLinks();
    handleOnAccsInputChange();
    handleRowsIndex();
    handleSelectLinksText();
    handleSignBtns();
    handleRowsToggle();

})();

</script>