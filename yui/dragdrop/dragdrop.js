YUI.add('moodle-qtype_ddmatch-dragdrop', function(Y, NAME) {

    var DDMATCHNAME = 'qtype_ddmatch-dragdrop';

    var DDMATCH = function() {
        DDMATCH.superclass.constructor.apply(this, arguments);
    };

    Y.extend(DDMATCH, Y.Base, {
        container : null,
        delegation : null,

        initializer : function() {
            if (this.get('readonly')) {
                // Don't apply any of the drag and drop magic if this form is readonly
                return;
            }

            var containerid = 'div#q' + this.get('questionid'),
                group = containerid + ' .matchtarget';

            // Set the container - we use this in various places
            this.container = Y.one(containerid);
            if (typeof this.container === 'null') {
                // If we can't find a valid question exit and leave the form in readonly state
                return;
            }

            this.delegation = new Y.DD.Delegate({
                container: this.container,
                nodes: 'li.matchdrag'
            });

            this.delegation.dd.addToGroup(group);

            this.container.all('li.matchdrag').setStyle('cursor', 'move');

            // Add the DDProxy so we only show the outline and can ensure
            // that the element isn't actually moved
            this.delegation.dd.plug(Y.Plugin.DDProxy, {
                moveOnEnd: false
            });

            // Constrain the drag action to just this question
            this.delegation.dd.plug(Y.Plugin.DDConstrained, {
                constrain2node: this.container
            });

            this.container.all('.matchtarget').each(function(curNode) {
                // Add drop targets to each matchtarget
                var drop = new Y.DD.Drop({
                    node: curNode,
                    groups: [ group ]
                });
            });

            this.delegation.dd.on('drag:drophit', this.handleHit, this);
            this.delegation.dd.on('drag:dropmiss', this.handleMiss, this);
        },
        handleHit : function(thisevent) {
            // Local variables
            var drag = thisevent.drag,
                drop = thisevent.drop,
                dragNode = drag.get('node'),
                dropNode = drop.get('node').ancestor('ul.matchtarget', true),
                copy, ancestor;

            if (dragNode.hasClass('copy')) {
                // This node is a copy, just move it
                copy = dragNode;

                // Unhide the old node's placeholder
                ancestor = dragNode.ancestor();
                ancestor.one('li.placeholder').removeClass('hidden');

                // Unset the input element value on the old ancestor
                this.setValue(ancestor);
            } else {
                // Create a copy of the element being dragged element
                copy = dragNode.cloneNode(true);
                copy.addClass('copy');
            }

            // Remove any other matchdrag elements
            dropNode.all('li.matchdrag').remove();

            // Append it to the target
            dropNode.appendChild(copy);

            // Hide the placeholder elemend on the drop node
            dropNode.one('li.placeholder').addClass('hidden');

            // Set the input element value on the dropNode
            this.setValue(dropNode, dragNode.getData('id'));

            // Resync the targets
            this.delegation.syncTargets();
        },
        handleMiss : function(thisevent) {
            // Local variables
            var dragNode = thisevent.target.get('node'),
                ancestor;

            // We only need to handle misses where a copied node was being dragged
            // Originals are returned by DDProxy
            if (dragNode.hasClass('copy')) {
                // Fetch the ancestor now - we'll need it later
                ancestor = dragNode.ancestor('ul.matchtarget');

                // Remove the node
                dragNode.remove();
                this.delegation.syncTargets();

                // Show the placeholder again
                ancestor.one('li.placeholder').removeClass('hidden');

                // Now clear the value for this ancestor
                this.setValue(ancestor);
            }
        },
        setValue : function(targetNode, value) {
            var selectname = targetNode.getData('selectname'),
                selectelement,
                selectoption;

            // Retrieve the element
            selectelement = this.container.one('select[name=' + selectname + ']');
            if (value) {
                // Attempt to set the value of the select to the relevant option
                selectoption = selectelement.one('option[value=' + value + ']');
                selectoption.set('selected', true);
            } else {
                // Attempt to set the value of the select to the first option
                selectelement.one('option').set('selected', true);
            }
            selectelement.get('value');
        }
    },
    {
        NAME : DDMATCHNAME,
        ATTRS : {
            questionid : {
                'type' : Number,
                'default' : null
            }
        }
    });

    M.qtype = M.qtype || {};
    M.qtype.ddmatch = M.qtype.ddmatch || {};
    M.qtype.ddmatch.init_dragdrop = function(config) {
        if (config.readonly === true) {
            // Don't instantiate the drag/drop if this form is readonly
            return {};
        }
        return new DDMATCH(config);
    };
}, '@VERSION@', {requires:['dd-delegate', 'dd-drop-plugin', 'dd-proxy', 'dd-constrain', 'selector-css3']});
