function formatDate(iso) {
  return iso ? iso.slice(0, 10) : ''
}

export function localizeNotification(notif, t) {
  const map = {
    order_paid: {
      title: t('notif.orderPaid'),
      message: t('notif.orderPaidMsg', { order_number: notif.data?.order_number ?? '' }),
    },
    vip_subscribed: {
      title: t('notif.vipSubscribed'),
      message: t('notif.vipSubscribedMsg', {
        plan_name: notif.data?.plan_name ?? '',
        ends_at: formatDate(notif.data?.ends_at),
      }),
    },
    review_approved: {
      title: t('notif.reviewApproved'),
      message: t('notif.reviewApprovedMsg', { book_title: notif.data?.book_title ?? '' }),
    },
    review_rejected: {
      title: t('notif.reviewRejected'),
      message: t('notif.reviewRejectedMsg', { book_title: notif.data?.book_title ?? '' }),
    },
  }

  return map[notif.type] ?? { title: notif.title, message: notif.message }
}