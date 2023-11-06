// eslint-disable-next-line no-unused-vars
import React, { useEffect } from 'react';
import PropTypes from 'prop-types'; // ES6
import { speak } from '@wordpress/a11y';
import { __ } from '@wordpress/i18n';
import { Notice as WPNotice } from '@wordpress/components';
import classNames from 'classnames';

const Notice = ( props ) => {
	const { message, status, politeness, icon, className, inline, children, hasToTop = false } = props;

	useEffect( () => {
		speak( message, politeness );
	}, [ message, status, politeness ] );

	const hasIcon = () => {
		return icon !== null;
	};
	const getIcon = ( Icon ) => {
		return <Icon width={ 16 } height={ 16 } fill="#6c757d" />;
	};

	const containerClasses = classNames( className, 'dlxgbhacks-admin__notice', {
		'dlxgbhacks-admin__notice--has-icon': hasIcon(),
		[ `dlxgbhacks-admin__notice-type--${ status }` ]: true,
		[ `dlxgbhacks-admin__notice-appearance--inline` ]: inline,
		[ `dlxgbhacks-admin__notice-appearance--block` ]: ! inline,
	} );

	const actions = [
		{
			label: __( 'Back to Top', 'wp-dlxgbhacks-comments' ),
			url: '#dlxgbhacks-admin-header',
			variant: 'link',
			className: 'dlxgbhacks-admin__notice-action dlxgbhacks-admin__notice-action--to-top',
		} ];
	return (
		<div className={ containerClasses }>
			<WPNotice isDismissible={ false } spokenMessage={ message } actions={ hasToTop ? actions : [] } { ...props }>
				{ hasIcon() &&
					<div className="dlxgbhacks-admin__notice-icon">{ getIcon( icon ) }</div>
				}
				<div className="dlxgbhacks-admin__notice-message"><>{ message } { children } </></div>
			</WPNotice>
		</div>
	);
};

Notice.defaultProps = {
	message: '',
	status: 'info',
	politeness: 'polite',
	icon: null,
	className: '',
	inline: false,
	hasToTop: false,
};

Notice.propTypes = {
	message: PropTypes.string.isRequired,
	status: PropTypes.oneOf( [ 'info', 'warning', 'success', 'error' ] ),
	politeness: PropTypes.oneOf( [ 'assertive', 'polite' ] ),
	icon: PropTypes.func,
	className: PropTypes.string,
	inline: PropTypes.bool,
	hasToTop: PropTypes.bool,
};

export default Notice;
