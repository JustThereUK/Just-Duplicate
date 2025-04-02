(function (wp) {
    const { registerPlugin } = wp.plugins;
    const { PluginPostStatusInfo } = wp.editPost;
    const { createElement } = wp.element;

    registerPlugin('just-duplicate', {
        render: () => {
            return createElement(
                PluginPostStatusInfo,
                null,
                createElement(
                    'a',
                    {
                        href: JustDuplicate.url,
                        className: 'button',
                    },
                    'Duplicate This'
                )
            );
        },
    });
})(window.wp);
