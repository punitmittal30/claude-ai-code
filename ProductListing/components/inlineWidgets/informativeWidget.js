import useResize from 'hooks/useResize'
import Link from 'next/link'
import React, { useEffect, useState } from 'react'
import { interactionDataLayer } from 'analytics/datalayer/navigate'
import { trackInteraction } from 'analytics/plugins/webEngage/navigate'
import { compressImage } from 'utils/index'

const InformativeWidget = ({ data }) => {
    const screen = useResize()
    const [content, setContent] = useState([])
    if (content && content.length) content.sort((a, b) => parseInt(a.priority) - parseInt(b.priority));
    useEffect(() => {
        if (data?.banners?.m_web?.length !== 0 && window.innerWidth < 475) {
            setContent(data?.banners?.m_web);
        } else if (data?.banners?.web?.length !== 0) {
            setContent(data?.banners?.web);
        } else {
            setContent([])
        }
    }, []);

    const trackClick = (i, el) => {
        interactionDataLayer("informative-widget-click", { 
            name: data?.title ?? undefined,
            position: i,
            value: el?.action_url ?? undefined,
            id: data?.priority ?? undefined
        });
        trackInteraction({
            action: "informative-widget-click",
            entity: {
                name: data?.title ?? undefined, 
                position: i,
                value: el?.action_url ?? undefined,
                id: data?.priority ?? undefined
            },
        });
    }

    return (
        <>
            {content && content?.length !== 0 ? <div className='health-in-focus-container'>
                {
                    screen.device === 'mobile' ?
                        <div className={`overflow-hidden flex flex-col bg-[#E5EEFF]`}>
                            {data?.title ? <p title={data?.title} className='text-lg cursor-pointer mb-2 text-gray-100 text-left font-semibold line-clamp-2 pt-4 pl-4'>{data?.title}</p> : null}
                            <div className="horizontal-scroll flex overflow-scroll pl-4 pb-6 pt-2">
                                {content?.map((el, i) => <div key={i} className='slider-horizontal-scroll pr-[12px] max-w-[240px]'>
                                    <Link href={el?.action_url ? el?.action_url : ''} passHref>
                                        <a onClick={() => trackClick(i, el)}>
                                            <img
                                                loading={"lazy"}
                                                alt={`${el?.title}`}
                                                className="w-full cursor-pointer rounded-xl"
                                                src={compressImage(el?.image_url)}
                                                width={192}
                                                height={176}
                                            />
                                        </a>
                                    </Link>
                                </div>)}
                            </div>
                        </div> :
                        <div className={`overflow-hidden flex flex-col bg-[#E5EEFF] mt-2 rounded-xl mb-5`}>
                        {data?.title ? <p title={data?.title} className='text-lg cursor-pointer mb-2 text-gray-100 text-left font-semibold line-clamp-2 pt-4 pl-4'>{data?.title}</p> : null}
                        <div className="horizontal-scroll flex overflow-scroll pl-4 pb-3 pt-2">
                            {content?.map((el, i) => <div key={i} className='slider-horizontal-scroll pr-2 max-w-[230px]'>
                                <Link href={el?.action_url ? el?.action_url : ''} passHref>
                                    <a onClick={() => trackClick(i, el)}>
                                        <img
                                            loading={"lazy"}
                                            alt={`${el?.title}`}
                                            className="w-full cursor-pointer rounded-xl"
                                            src={compressImage(el?.image_url)}
                                            width={192}
                                            height={176}
                                        />
                                    </a>
                                </Link>
                            </div>)}
                        </div>
                    </div>
                }
            </div> : null}
        </>
    )
}

export default InformativeWidget;