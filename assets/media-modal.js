/* global wp, jQuery, DokaBunny */
(function ($) {
    if (!wp || !wp.media) return;

    const apiFetchVideos = (page, search) => {
        return $.ajax({
            method: 'GET',
            url: DokaBunny.videos,
            data: { page: page || 1, search: search || '' },
            headers: { 'X-WP-Nonce': DokaBunny.nonce }
        });
    };

    // Create a custom media frame tab that lists Bunny videos.
    const BunnyBrowser = wp.media.View.extend({
        className: 'doka-bunny-browser',
        template: wp.template('doka-bunny-browser'),
        events: {
            'input .doka-bunny-search': 'onSearch',
            'click .doka-bunny-item': 'onSelect',
            'click .doka-bunny-loadmore': 'onLoadMore'
        },
        initialize() {
            this.items = [];
            this.page = 1;
            this.search = '';
            this.loading = false;
            this.more = true;
            this.fetch();
        },
        fetch() {
            if (this.loading || !this.more) return;
            this.loading = true;
            apiFetchVideos(this.page, this.search).done((res) => {
                const newItems = (res && res.items) ? res.items : [];
                this.items = this.items.concat(newItems);
                this.more  = newItems.length > 0; // naive: if zero, stop
                this.page += 1;
                this.loading = false;
                this.render();
            }).fail(() => {
                this.loading = false;
                this.more = false;
                this.render();
            });
        },
        onSearch(e) {
            this.search = e.target.value || '';
            this.page = 1;
            this.items = [];
            this.more = true;
            this.fetch();
        },
        onLoadMore(e) {
            e.preventDefault();
            this.fetch();
        },
        onSelect(e) {
            e.preventDefault();
            const $item = $(e.currentTarget);
            const id = $item.data('id');
            if (!id) return;

            // Insert shortcode into the editor.
            /* eslint-disable no-undef */
            wp.media.editor.insert(DokaBunny.insertTpl.replace('%s', id));
            /* eslint-enable no-undef */
            this.controller.state().trigger('close');
        },
        getTemplateData() {
            return {
                i18n: DokaBunny.i18n,
                items: this.items,
                loading: this.loading,
                more: this.more
            };
        }
    });

    // Extend the media frame with our new tab.
    const original = wp.media.view.MediaFrame.Select;
    wp.media.view.MediaFrame.Select = original.extend({
        bindHandlers() {
            original.prototype.bindHandlers.apply(this, arguments);
            this.on('content:create:bunny', this.createBunnyContent, this);
        },
        createStates() {
            original.prototype.createStates.apply(this, arguments);

            this.states.add([
                new wp.media.controller.State({
                    id: 'bunny',
                    menu: 'default',
                    // No library required because we render our own browser UI.
                })
            ]);
        },
        browseRouter(routerView) {
            original.prototype.browseRouter.apply(this, arguments);
            routerView.set({
                bunny: {
                    text: DokaBunny.i18n.tabTitle,
                    priority: 60
                }
            });
        },
        createBunnyContent(content) {
            const view = new BunnyBrowser({
                controller: this
            });
            content.view = view;
        }
    });

    // Underscore template for our grid.
    $('body').append(
        '<script type="text/html" id="tmpl-doka-bunny-browser">' +
            '<div class="doka-bunny-toolbar">' +
                '<input type="search" class="doka-bunny-search" placeholder="{{ i18n.search }}" />' +
            '</div>' +
            '<div class="doka-bunny-grid">' +
                '<# if ( items && items.length ) { #>' +
                    '<# _.each( items, function( it ){ #>' +
                        '<div class="doka-bunny-item" data-id="{{ it.id }}">' +
                            '<div class="thumb" style="background-image:url({{ it.thumb || "" }})"></div>' +
                            '<div class="title">{{ it.title || it.id }}</div>' +
                        '</div>' +
                    '<# }); #>' +
                '<# } else { #>' +
                    '<div class="doka-bunny-empty">No results.</div>' +
                '<# } #>' +
            '</div>' +
            '<div class="doka-bunny-footer">' +
                '<# if ( more ) { #><button class="button button-secondary doka-bunny-loadmore">Load more</button><# } #>' +
                '<# if ( loading ) { #><span class="spinner is-active" style="float:none;"></span><# } #>' +
            '</div>' +
        '</script>'
    );
})(jQuery);
