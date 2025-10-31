import mitt from 'mitt';

type Events = {
    created: { entity: string; record: any };
};

const emitter = mitt<Events>();

export function useEntityEvents() {
    return {
        on: emitter.on,
        off: emitter.off,
        emit: emitter.emit,
    };
}
