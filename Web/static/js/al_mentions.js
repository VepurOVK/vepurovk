var tooltipTemplate = Handlebars.compile(`
    <table>
        <tr>
            <td width="54" valign="top">
                <img src="{{ava}}" width="54" />
            </td>
            <td width="1"></td>
            <td width="150" valign="top">
                <span>
                    <a href="{{url}}"><b>{{name}}</b></a>
                    {{#if verif}}
                        <img class="name-checkmark" src="/assets/packages/static/openvk/img/checkmark.png" />
                    {{/if}}
                </span><br/>
                <span style="color: #444;">{{online}}</span><br/>
                <span style="color: #000;">{{about}}</span>
            </td>
        </tr>
    </table>
`);

tippy.delegate("body", {
    target: '.mention',
    theme: "light vk",
    content: "⌛",
    delay: 200,
    allowHTML: true,
    interactive: true,
    interactiveDebounce: 500,

    onCreate: async function(that) {
        that._resolvedMention = null;
    },

    onShow: async function(that) {
        if(!that._resolvedMention) {
            let id = Number(that.reference.dataset.mentionRef);
            that._resolvedMention = await API.Mentions.resolve(id);
        }

        let res = that._resolvedMention;
        that.setContent(tooltipTemplate(res));
    }
});
